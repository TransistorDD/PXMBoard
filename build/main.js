import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import Underline from '@tiptap/extension-underline'
import Mention from '@tiptap/extension-mention'
import Youtube from '@tiptap/extension-youtube'
import Twitch from '@tiptap/extension-twitch'
import { Plugin, PluginKey } from '@tiptap/pm/state'
import suggestion from './mention-suggestion.js'
import { Spoiler, MemberContent } from './pxm-extensions.js'
import { createYouTubeNodeView, createTwitchNodeView } from './video-nodeviews.js'

/**
 * Custom Link Extension
 * - Renders the URL as visible text instead of hidden behind link text
 * - When text is selected, replaces it with the URL
 * - Automatically synchronizes link text with href attribute when changed
 * This ensures URLs are always visible in the editor and parsed content
 */
const CustomLink = Link.extend({
  addCommands() {
    return {
      ...this.parent?.(),
      setLink: (attributes) => ({ chain, state }) => {
        const { from, to } = state.selection;
        const hasSelection = from !== to;

        if (hasSelection) {
          // If text is selected: Replace it with the URL
          return chain()
            .deleteSelection()
            .insertContent({
              type: 'text',
              text: attributes.href,
              marks: [
                {
                  type: this.name,
                  attrs: attributes,
                },
              ],
            })
            .run();
        }

        // Default behavior if nothing is selected
        return chain()
          .setMark(this.name, attributes)
          .run();
      },
    };
  },

  addProseMirrorPlugins() {
    return [
      new Plugin({
        key: new PluginKey('linkTextSync'),
        appendTransaction: (transactions, oldState, newState) => {
          // Only check if there were actual changes
          if (!transactions.some(tr => tr.docChanged)) {
            return null;
          }

          const tr = newState.tr;
          let modified = false;

          // Iterate through the document to find link marks
          newState.doc.descendants((node, pos) => {
            if (!node.isText) {
              return;
            }

            // Check if this text node has a link mark
            const linkMark = node.marks.find(mark => mark.type.name === 'link');
            if (!linkMark) {
              return;
            }

            // Check if the text differs from href
            const href = linkMark.attrs.href;
            if (node.text !== href) {
              // Update href to match text (text → URL sync)
              const newMark = linkMark.type.create({
                ...linkMark.attrs,
                href: node.text
              });

              // Remove old mark and add new one with updated href
              tr.removeMark(pos, pos + node.nodeSize, linkMark.type);
              tr.addMark(pos, pos + node.nodeSize, newMark);
              modified = true;
            }
          });

          return modified ? tr : null;
        },
      }),
    ];
  },

  renderHTML({ HTMLAttributes }) {
    return [
      'a',
      HTMLAttributes,
      HTMLAttributes.href // URL is visible text
    ]
  },
})

/**
 * Tiptap to PXM Converter
 * Konvertiert Tiptap JSON zu PXM-Syntax
 * Basierend auf der Stack-basierten PXM-Syntax:
 *   [b:text] → <b>text</b>
 *   [b:[i:text]] → <b><i>text</i></b>
 *
 * WICHTIG: Diese Funktion MUSS synchron mit cMessageHtmlParser::parse() in PHP gehalten werden!
 * Jede Änderung an der Konvertierungs-Logik muss in beiden Dateien nachgezogen werden.
 *
 * @see include/parser/cMessageHtmlParser.php::parse() - Server-seitige Parsing-Logik
 */
function tiptapToPxm(editor) {
  const json = editor.getJSON();

  const convertNode = (node) => {
    // Text-Node mit Marks
    if (node.type === 'text') {
      let text = node.text;

      // Marks von außen nach innen anwenden (Stack-basiert)
      if (node.marks) {
        // Reverse, damit innerste Marks zuerst applied werden
        node.marks.slice().reverse().forEach(mark => {
          switch(mark.type) {
            case 'bold':
              text = `[b:${text}]`;
              break;
            case 'italic':
              text = `[i:${text}]`;
              break;
            case 'underline':
              text = `[u:${text}]`;
              break;
            case 'strike':
              text = `[s:${text}]`;
              break;
            case 'link':
              // Link-Format: [url]
              const href = mark.attrs.href;
              if (href.startsWith('http://') || href.startsWith('https://')) {
                text = `[${href}]`;
              } else if (href.startsWith('www.')) {
                text = `[${href}]`;
              } else if (href.startsWith('mailto:')) {
                text = `[${href}]`;
              }
              break;
          }
        });
      }
      return text;
    }

    // Mention-Node handling: Convert to PXM format [user:id]
    if (node.type === 'mention') {
      const userId = node.attrs?.id || 0;
      return `[user:${userId}]`;
    }

    // Block-Nodes rekursiv verarbeiten
    const content = node.content
      ? node.content.map(convertNode).join('')
      : '';

    switch(node.type) {
      case 'paragraph':
        // Paragraph = Content + Newline
        return content + '\n';

      case 'blockquote':
        // Quote mit [q:] Tag
        return `[q:${content.trim()}]`;

      case 'spoiler':
        // Spoiler mit [h:] Tag
        return `[h:${content.trim()}]`;

      case 'memberContent':
        // Mitglieder-Inhalt mit [m:] Tag
        return `[m:${content.trim()}]`;

      case 'hardBreak':
        return '\n';

      case 'image':
        // Bild-Format: [img:url]
        const src = node.attrs?.src || '';
        return `[img:${src}]`;

      case 'youtube':
        // YouTube-Format: [yt:videoId oder URL]
        const ytSrc = node.attrs?.src || '';
        return `[yt:${ytSrc}]`;

      case 'twitch':
        // Twitch-Format: [ttv:URL]
        const ttvSrc = node.attrs?.src || '';
        return `[ttv:${ttvSrc}]`;

      case 'doc':
        return content;

      default:
        console.warn('Unbekannter Node-Typ:', node.type);
        return content;
    }
  };

  return convertNode(json).trim();
}

/**
 * Editor-Instanzen werden außerhalb von Alpine's Reactive-System gespeichert,
 * damit ProseMirror's State-Management nicht durch Alpine's Proxy gestört wird.
 */
const editorInstances = new WeakMap();

function getEditor(el) {
  return editorInstances.get(el);
}

/**
 * Alpine.js Component für Tiptap Editor
 */
let pxmEditorRegistered = false;

function initPxmEditor() {
  if (pxmEditorRegistered) {
    return;
  }

  if (typeof window.Alpine !== 'undefined') {
    pxmEditorRegistered = true;

    window.Alpine.data('pxmEditor', (initialHtmlContent = '') => ({
      pxmContent: '',
      loading: true,
      error: null,
      _transactionVersion: 0,

      init() {
        const el = this.$refs.editor;
        if (!el || getEditor(el)) {
          return;
        }

        try {
          const component = this;
          const editor = new Editor({
            element: el,
            extensions: [
              StarterKit.configure({
                link: false,
                underline: false,
                history: {
                  depth: 50,
                  newGroupDelay: 500,
                },
              }),
              Underline,
              Spoiler,
              MemberContent,
              CustomLink.configure({
                openOnClick: false,
                HTMLAttributes: {
                  target: '_blank',
                  rel: 'noopener noreferrer'
                },
                // Validate URLs to match cPxmParser.php requirements (line 161)
                // /^\[((https?|ftps?|www|mailto:)([^\] ]+))/iu
                isAllowedUri: (url, ctx) => {
                  // Allow default validation first
                  if (!ctx.defaultValidate(url)) {
                    return false;
                  }
                  // PXM-specific protocol validation
                  const pxmPattern = /^(https?|ftps?|www\.|mailto:)/i;
                  return pxmPattern.test(url);
                },
                protocols: [
                  { scheme: 'ftp', optionalSlashes: false },
                  { scheme: 'ftps', optionalSlashes: false },
                  { scheme: 'mailto', optionalSlashes: true }
                ],
                defaultProtocol: 'https'
              }),
              Image.configure({
                inline: false,
                HTMLAttributes: {
                  class: 'max-w-full h-auto'
                }
              }),
              Mention.configure({
                HTMLAttributes: {
                  class: 'mention',
                },
                suggestion: suggestion,
                renderLabel({ node }) {
                  return `@${node.attrs.label}`;
                }
              }),
              Youtube.configure({
                width: 640,
                height: 480,
                addPasteHandler: true,
                HTMLAttributes: {
                  class: 'youtube-embed'
                }
              }).extend({
                addNodeView() {
                  return ({ node }) => createYouTubeNodeView(node);
                }
              }),
              Twitch.configure({
                width: 640,
                height: 480,
                parent: (window.location.hostname || 'localhost').split(':')[0],
                addPasteHandler: true,
                HTMLAttributes: {
                  class: 'twitch-embed'
                }
              }).extend({
                addNodeView() {
                  return ({ node }) => createTwitchNodeView(node);
                }
              })
            ],
            content: initialHtmlContent,
            onUpdate: ({ editor }) => {
              component.pxmContent = tiptapToPxm(editor);
            },
            onTransaction: () => {
              component._transactionVersion++;
            },
            editorProps: {
              attributes: {
                class: 'prose prose-sm'
              }
            }
          });

          editorInstances.set(el, editor);
          this.pxmContent = tiptapToPxm(editor);
          this.loading = false;
        } catch (error) {
          console.error('Editor-Initialisierung fehlgeschlagen:', error);
          this.error = 'Editor konnte nicht geladen werden. Fehler: ' + error.message;
          this.loading = false;
        }
      },

      destroy() {
        const el = this.$refs.editor;
        const editor = el && getEditor(el);
        if (editor && !editor.isDestroyed) {
          editor.destroy();
        }
        if (el) {
          editorInstances.delete(el);
        }
      },

      // Toolbar-Aktionen
      toggleBold() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;
        editor.commands.toggleBold();
      },

      toggleItalic() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;
        editor.commands.toggleItalic();
      },

      toggleUnderline() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;
        editor.commands.toggleUnderline();
      },

      toggleStrike() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;
        editor.commands.toggleStrike();
      },

      /**
       * Toggle Link (add or remove)
       * CustomLink extension renders URL as visible text
       * Validation configured in CustomLink extension (isAllowedUri) matches cPxmParser.php regex on line 161
       *
       * @see include/parser/cPxmParser.php:161 - Link parsing regex
       */
      toggleLink() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;

        // If link is active, remove it
        if (editor.isActive('link')) {
          editor.commands.unsetLink();
          return;
        }

        // Get selected text to pre-fill dialog
        const { from, to } = editor.state.selection;
        const selectedText = editor.state.doc.textBetween(from, to, '');

        // Otherwise, prompt for URL (pre-fill with selected text if available)
        const url = window.prompt(
          'URL eingeben:\n\nErlaubte Formate:\n• http://beispiel.de\n• https://beispiel.de\n• ftp://server.de\n• ftps://server.de\n• www.beispiel.de\n• mailto:email@beispiel.de',
          selectedText || 'https://'
        );
        if (url && url.trim() !== '' && url.trim() !== 'https://') {
          // Try to set link - Tiptap's isAllowedUri will validate
          const success = editor.commands.setLink({ href: url.trim() });
          if (!success) {
            alert('Ungültige URL. Bitte eines der erlaubten Formate verwenden.');
          }
        }
      },

      /**
       * Insert Image
       * Validation must match cPxmParser.php regex on line 173:
       * /^\[img:((https?[^\] ]+)\.(?:jpg|gif|png|jpeg))/iu
       *
       * @see include/parser/cPxmParser.php:173 - Image parsing regex
       */
      insertImage() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;
        const url = window.prompt(
          'Bild-URL eingeben:\n\nErlaubte Formate:\n• http://beispiel.de/bild.jpg\n• https://beispiel.de/bild.png\n\nDateiendungen: .jpg, .jpeg, .png, .gif',
          'https://'
        );
        if (url && url.trim() !== '' && url.trim() !== 'https://') {
          // Validate image URL (must match cPxmParser.php validation)
          const imgPattern = /^https?[^\s\]]+\.(jpg|gif|png|jpeg)$/i;
          if (!imgPattern.test(url.trim())) {
            alert('Ungültige Bild-URL.\n\nBitte vollständige URL mit Dateiendung angeben:\n• .jpg, .jpeg, .png oder .gif');
            return;
          }
          editor.commands.setImage({ src: url.trim() });
        }
      },

      /**
       * Insert YouTube Video
       * Only accepts full URLs (no video IDs)
       * Validation must match cPxmParser.php _extractYouTubeId() on lines 409-423
       *
       * @see include/parser/cPxmParser.php:409-423 - YouTube ID extraction
       */
      insertYoutube() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;
        const url = window.prompt(
          'YouTube-Video einfügen:\n\nErlaubte Formate:\n• https://youtube.com/watch?v=dQw4w9WgXcQ\n• https://youtu.be/dQw4w9WgXcQ',
          'https://youtube.com/watch?v='
        );
        if (url && url.trim() !== '' && url.trim() !== 'https://youtube.com/watch?v=') {
          // Validate YouTube format (must match cPxmParser.php validation)
          const trimmedUrl = url.trim();
          const youtubeWatch = /youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/;
          const youtubeShort = /youtu\.be\/([a-zA-Z0-9_-]{11})/;

          if (!youtubeWatch.test(trimmedUrl) && !youtubeShort.test(trimmedUrl)) {
            alert('Ungültige YouTube-URL.\n\nBitte vollständige YouTube-URL eingeben.');
            return;
          }

          editor.commands.setYoutubeVideo({ src: trimmedUrl });
        }
      },

      /**
       * Insert Twitch Video/Clip/Channel
       * Validation must match cPxmParser.php _extractTwitchData() on lines 435-456
       *
       * @see include/parser/cPxmParser.php:435-456 - Twitch URL extraction
       */
      insertTwitch() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;
        const url = window.prompt(
          'Twitch-Video/Clip/Channel einfügen:\n\nErlaubte Formate:\n• Video: https://twitch.tv/videos/123456789\n• Clip: https://clips.twitch.tv/ClipName\n• Clip: https://twitch.tv/channel/clip/ClipName\n• Channel: https://twitch.tv/channelname',
          'https://twitch.tv/'
        );
        if (url && url.trim() !== '' && url.trim() !== 'https://twitch.tv/') {
          // Validate Twitch format (must match cPxmParser.php validation)
          const trimmedUrl = url.trim();
          const twitchVideo = /twitch\.tv\/videos\/\d+/;
          const twitchClip = /(clips\.twitch\.tv\/[a-zA-Z0-9_-]+|twitch\.tv\/[^\/]+\/clip\/[a-zA-Z0-9_-]+)/;
          const twitchChannel = /twitch\.tv\/[a-zA-Z0-9_]+$/;

          if (!twitchVideo.test(trimmedUrl) && !twitchClip.test(trimmedUrl) && !twitchChannel.test(trimmedUrl)) {
            alert('Ungültige Twitch-URL.\n\nBitte vollständige Twitch-URL eingeben (Video, Clip oder Channel).');
            return;
          }
          editor.commands.setTwitchVideo({ src: trimmedUrl });
        }
      },

      toggleBlockquote() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;
        editor.commands.toggleBlockquote();
      },

      toggleSpoiler() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;
        editor.commands.toggleSpoiler();
      },

      toggleMemberContent() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;
        editor.commands.toggleMemberContent();
      },

      undo() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;
        editor.commands.undo();
      },

      redo() {
        const editor = getEditor(this.$refs.editor);
        if (!editor || editor.isDestroyed) return;
        editor.commands.redo();
      },

      // Status-Prüfungen für Toolbar Buttons
      // _transactionVersion wird bei jeder Editor-Transaktion inkrementiert,
      // damit Alpine.js die reaktive Abhängigkeit erkennt und die Buttons aktualisiert
      isBold() {
        this._transactionVersion;
        return getEditor(this.$refs.editor)?.isActive('bold');
      },

      isItalic() {
        this._transactionVersion;
        return getEditor(this.$refs.editor)?.isActive('italic');
      },

      isUnderline() {
        this._transactionVersion;
        return getEditor(this.$refs.editor)?.isActive('underline');
      },

      isStrike() {
        this._transactionVersion;
        return getEditor(this.$refs.editor)?.isActive('strike');
      },

      isLink() {
        this._transactionVersion;
        return getEditor(this.$refs.editor)?.isActive('link');
      },

      isBlockquote() {
        this._transactionVersion;
        return getEditor(this.$refs.editor)?.isActive('blockquote');
      },

      isSpoiler() {
        this._transactionVersion;
        return getEditor(this.$refs.editor)?.isActive('spoiler');
      },

      isMemberContent() {
        this._transactionVersion;
        return getEditor(this.$refs.editor)?.isActive('memberContent');
      }
    }));
  }
}

// Versuche mehrere Event-Strategien, aber registriere nur einmal
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPxmEditor);
} else {
  initPxmEditor();
}

// Fallback: Alpine-spezifisches Event
document.addEventListener('alpine:init', initPxmEditor);

/**
 * Global spoiler toggle function
 * Called from onclick handler in parsed HTML
 */
window.spoiler = function(button) {
  const spoilerContent = button.nextElementSibling;
  if (spoilerContent && spoilerContent.classList.contains('spoiler')) {
    spoilerContent.classList.toggle('hidden');
    const textSpan = button.querySelector('span:last-child');
    if (textSpan) {
      textSpan.textContent = spoilerContent.classList.contains('hidden')
        ? 'Spoiler anzeigen'
        : 'Spoiler verbergen';
    }
  }
};
