/**
 * Mention Suggestion Configuration for Tiptap
 *
 * Provides autocomplete functionality for @mentions with AJAX user search
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */

export default {
  // Character that triggers the autocomplete
  char: '@',

  // Items to show in the suggestion list
  items: async ({ query }) => {
    // Min 2 characters for search
    if (query.length < 2) {
      return [];
    }

    try {
      // Call AJAX endpoint
      const response = await fetch(`pxmboard.php?mode=ajaxUserautocomplete&q=${encodeURIComponent(query)}`);

      const data = await response.json();

      if (!response.ok) {
        return [{ _error: data.error || `Fehler ${response.status}` }];
      }

      return data.results || [];
    } catch (error) {
      return [{ _error: 'Verbindungsfehler' }];
    }
  },

  // Render the suggestion dropdown (simplified, no tippy dependency)
  render: () => {
    let component;
    let popup;

    return {
      onStart: props => {
        component = new MentionList({
          props,
          editor: props.editor,
        });

        // Create popup wrapper
        popup = document.createElement('div');
        popup.className = 'mention-popup';
        popup.style.position = 'absolute';
        popup.style.zIndex = '9999';
        popup.appendChild(component.element);
        document.body.appendChild(popup);

        // Position at cursor (including scroll offset)
        const rect = props.clientRect();
        if (rect) {
          popup.style.top = (rect.bottom + window.scrollY) + 'px';
          popup.style.left = (rect.left + window.scrollX) + 'px';
        }
      },

      onUpdate(props) {
        component.updateProps(props);

        // Update position (including scroll offset)
        const rect = props.clientRect();
        if (rect && popup) {
          popup.style.top = (rect.bottom + window.scrollY) + 'px';
          popup.style.left = (rect.left + window.scrollX) + 'px';
        }
      },

      onKeyDown(props) {
        if (props.event.key === 'Escape') {
          if (popup && popup.parentNode) {
            popup.parentNode.removeChild(popup);
          }
          return true;
        }

        return component.onKeyDown(props);
      },

      onExit() {
        if (popup && popup.parentNode) {
          popup.parentNode.removeChild(popup);
        }
        component.destroy();
      },
    };
  },
};

/**
 * Simple Mention List Component
 * Renders the autocomplete dropdown with keyboard navigation
 */
class MentionList {
  constructor({ props, editor }) {
    this.props = props;
    this.editor = editor;
    this.selectedIndex = 0;

    this.element = document.createElement('div');
    this.element.className = 'mention-list bg-white border border-gray-300 rounded shadow-lg max-h-60 overflow-auto';

    this.render();
  }

  render() {
    const items = this.props.items;
    const query = this.props.query || '';

    // Error from API
    if (items.length === 1 && items[0]._error) {
      this.element.innerHTML = `<div class="px-3 py-2 text-red-600 text-sm">${items[0]._error}</div>`;
      return;
    }

    // Don't show "no results" if user hasn't typed enough yet
    if (items.length === 0) {
      if (query.length < 2) {
        this.element.innerHTML = '<div class="px-3 py-2 text-gray-500 text-sm">Tippe mindestens 2 Zeichen...</div>';
      } else {
        this.element.innerHTML = '<div class="px-3 py-2 text-gray-500 text-sm">Keine Benutzer gefunden</div>';
      }
      return;
    }

    this.element.innerHTML = items
      .map((item, index) => {
        const isSelected = index === this.selectedIndex;
        return `
          <button
            class="mention-item w-full text-left px-3 py-2 hover:bg-blue-50 ${isSelected ? 'bg-blue-100' : ''}"
            data-index="${index}"
          >
            <span class="font-medium">@${item.label}</span>
          </button>
        `;
      })
      .join('');

    // Add click handlers
    this.element.querySelectorAll('.mention-item').forEach((button, index) => {
      button.addEventListener('click', () => this.selectItem(index));
    });
  }

  updateProps(props) {
    this.props = props;
    this.selectedIndex = 0;
    this.render();
  }

  onKeyDown({ event }) {
    const items = this.props.items;

    if (event.key === 'ArrowUp') {
      this.selectedIndex = ((this.selectedIndex + items.length - 1) % items.length);
      this.render();
      return true;
    }

    if (event.key === 'ArrowDown') {
      this.selectedIndex = ((this.selectedIndex + 1) % items.length);
      this.render();
      return true;
    }

    if (event.key === 'Enter') {
      this.selectItem(this.selectedIndex);
      return true;
    }

    return false;
  }

  selectItem(index) {
    const item = this.props.items[index];

    if (item) {
      this.props.command({ id: item.id, label: item.label });
    }
  }

  destroy() {
    this.element.remove();
  }
}
