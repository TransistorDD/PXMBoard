/**
 * Custom NodeViews für YouTube und Twitch Video-Embeds
 * Zeigt sichtbare Platzhalter im Editor statt leerer iframes
 */

/**
 * YouTube NodeView - Zeigt Icon und URL als Platzhalter
 */
export function createYouTubeNodeView(node) {
  const dom = document.createElement('div');
  dom.className = 'youtube-placeholder';

  const icon = document.createElement('div');
  icon.className = 'video-placeholder-icon';
  icon.textContent = '▶️';

  const text = document.createElement('div');
  text.className = 'video-placeholder-text';
  const videoId = node.attrs.src || 'Unbekannte Video-ID';
  text.textContent = `YouTube: ${videoId}`;

  dom.appendChild(icon);
  dom.appendChild(text);

  return {
    dom,
    // Verhindere Inhalt-Editierung
    contentDOM: null,
    // Node ist selektierbar und löschbar
    ignoreMutation: () => true,
  };
}

/**
 * Twitch NodeView - Zeigt Icon und URL als Platzhalter
 */
export function createTwitchNodeView(node) {
  const dom = document.createElement('div');
  dom.className = 'twitch-placeholder';

  const icon = document.createElement('div');
  icon.className = 'video-placeholder-icon';
  icon.textContent = '🎮';

  const text = document.createElement('div');
  text.className = 'video-placeholder-text';
  const url = node.attrs.src || 'Unbekannte URL';
  text.textContent = `Twitch: ${url}`;

  dom.appendChild(icon);
  dom.appendChild(text);

  return {
    dom,
    // Verhindere Inhalt-Editierung
    contentDOM: null,
    // Node ist selektierbar und löschbar
    ignoreMutation: () => true,
  };
}
