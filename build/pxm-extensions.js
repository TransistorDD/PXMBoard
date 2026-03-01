/**
 * Custom Tiptap Extensions für PXM-Format
 *
 * WICHTIG: Diese Extensions MÜSSEN synchron mit cMessageHtmlParser::parse() in PHP gehalten werden!
 * Jede Änderung muss in beiden Dateien nachgezogen werden.
 *
 * @see include/parser/cMessageHtmlParser.php::parse() - Server-seitige Parsing-Logik
 */

import { Node } from '@tiptap/core'

/**
 * Spoiler/Hidden Content Extension (Block-Node)
 * PXM-Format: [h:hidden text]
 * Verhält sich wie Blockquote: umschließt mehrere Zeilen, Doppel-Enter verlässt den Bereich
 *
 * @see include/parser/cMessageHtmlParser.php::parse() - Server-seitige Parsing-Logik
 */
export const Spoiler = Node.create({
  name: 'spoiler',
  group: 'block',
  content: 'block+',
  defining: true,

  parseHTML() {
    return [
      { tag: 'div.spoiler' },
    ]
  },

  renderHTML() {
    return ['div', { class: 'spoiler' }, 0]
  },

  addCommands() {
    return {
      toggleSpoiler: () => ({ commands }) => {
        return commands.toggleWrap(this.name)
      },
    }
  },
})

/**
 * Member-Only Content Extension (Block-Node)
 * PXM-Format: [m:member content]
 * Verhält sich wie Blockquote: umschließt mehrere Zeilen, Doppel-Enter verlässt den Bereich
 *
 * @see include/parser/cMessageHtmlParser.php::parse() - Server-seitige Parsing-Logik
 */
export const MemberContent = Node.create({
  name: 'memberContent',
  group: 'block',
  content: 'block+',
  defining: true,

  parseHTML() {
    return [
      { tag: 'div.member-content' },
    ]
  },

  renderHTML() {
    return ['div', { class: 'member-content' }, 0]
  },

  addCommands() {
    return {
      toggleMemberContent: () => ({ commands }) => {
        return commands.toggleWrap(this.name)
      },
    }
  },
})
