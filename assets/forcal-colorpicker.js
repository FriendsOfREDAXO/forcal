/**
 * ForCal ColorPicker
 * Ein leichter ColorPicker ohne jQuery-Abhängigkeiten
 * (aber per rex:ready initialisiert)
 */
class ForcalColorPicker {
  constructor(element, options = {}) {
    this.inputElement = element;
    
    // Farben aus dem data-palette-Attribut auslesen, falls vorhanden
    let customColors = [];
    if (this.inputElement.hasAttribute('data-palette')) {
      try {
        // Versuche, das data-palette-Attribut als JSON zu parsen
        customColors = JSON.parse(this.inputElement.getAttribute('data-palette'));
      } catch (e) {
        console.error("Fehler beim Parsen des data-palette-Attributs:", e);
      }
    }

    // Optionen zusammenführen, mit Priorität für benutzerdefinierte Farben
    this.options = Object.assign({
      format: 'hex',
      defaultColor: '#3498db',
      clear_btn: 'last',
      colors: [
        '#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6', 
        '#1abc9c', '#34495e', '#e67e22', '#95a5a6', '#ffffff'
      ]
    }, options);

    // Eigene Farben aus dem data-palette-Attribut haben Priorität
    if (customColors.length > 0) {
      this.options.colors = customColors;
    }
    
    this.currentColor = this.inputElement.value || this.options.defaultColor;
    this.init();
  }
  
  init() {
    // Erstelle das Picker-Element
    this.createElements();
    this.attachEvents();
    
    // Anfänglichen Wert setzen
    this.updateColor(this.currentColor, false);
  }
  
  createElements() {
    // Wrapper erstellen
    this.wrapper = document.createElement('div');
    this.wrapper.className = 'forcal-colorpicker-wrapper';
    
    // Farb-Preview
    this.preview = document.createElement('div');
    this.preview.className = 'forcal-colorpicker-preview';
    
    // Popup-Container
    this.popup = document.createElement('div');
    this.popup.className = 'forcal-colorpicker-popup';
    this.popup.style.display = 'none';
    
    // Preset-Farben
    const presetContainer = document.createElement('div');
    presetContainer.className = 'forcal-colorpicker-presets';
    
    this.options.colors.forEach(color => {
      const preset = document.createElement('div');
      preset.className = 'forcal-colorpicker-preset';
      preset.style.backgroundColor = color;
      preset.setAttribute('data-color', color);
      preset.addEventListener('click', () => this.updateColor(color));
      presetContainer.appendChild(preset);
    });
    
    // Clear-Button
    if (this.options.clear_btn) {
      const clearBtn = document.createElement('div');
      clearBtn.className = 'forcal-colorpicker-clear';
      clearBtn.textContent = '✕';
      clearBtn.addEventListener('click', () => this.updateColor(''));
      
      // Clear-Button Position basierend auf der Option
      if (this.options.clear_btn === 'last') {
        presetContainer.appendChild(clearBtn);
      } else {
        presetContainer.insertBefore(clearBtn, presetContainer.firstChild);
      }
    }
    
    // DOM aufbauen
    this.popup.appendChild(presetContainer);
    
    this.wrapper.appendChild(this.preview);
    this.wrapper.appendChild(this.popup);
    
    // Originaleingabefeld verstecken und Wrapper einfügen
    this.inputElement.style.display = 'none';
    this.inputElement.parentNode.insertBefore(this.wrapper, this.inputElement);
  }
  
  attachEvents() {
    // Toggle des Popup-Menüs beim Klick auf den Preview
    this.preview.addEventListener('click', (e) => {
      e.stopPropagation();
      this.togglePopup();
    });
    
    // Popup schließen, wenn außerhalb geklickt wird
    document.addEventListener('click', (e) => {
      if (!this.wrapper.contains(e.target)) {
        this.hidePopup();
      }
    });
    
    // ESC-Taste zum Schließen
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.hidePopup();
      }
    });
  }
  
  updateColor(color, hidePopup = true) {
    this.currentColor = color;
    
    // Update des Vorschau-Elements
    if (color) {
      this.preview.style.backgroundColor = color;
      this.preview.classList.remove('no-color');
    } else {
      this.preview.style.backgroundColor = '';
      this.preview.classList.add('no-color');
    }
    
    // Update des versteckten Input-Feldes
    this.inputElement.value = color;
    
    // Event auslösen für eventuelle Abhängigkeiten
    const event = new Event('change', { bubbles: true });
    this.inputElement.dispatchEvent(event);
    
    if (hidePopup) {
      this.hidePopup();
    }
  }
  
  togglePopup() {
    if (this.popup.style.display === 'none') {
      this.showPopup();
    } else {
      this.hidePopup();
    }
  }
  
  showPopup() {
    // Aktiven Button markieren
    const allPresets = this.popup.querySelectorAll('.forcal-colorpicker-preset');
    allPresets.forEach(preset => {
      preset.classList.remove('active');
      if (preset.getAttribute('data-color') === this.currentColor) {
        preset.classList.add('active');
      }
    });
    
    this.popup.style.display = 'block';
    
    // Position des Popups überprüfen und ggf. anpassen
    const bounds = this.popup.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;
    
    // Prüfen, ob wir auf einem kleinen Bildschirm sind
    const isMobileView = viewportWidth <= 576;
    
    // Nur auf Desktop die Position automatisch anpassen
    if (!isMobileView && bounds.bottom > viewportHeight) {
      this.popup.classList.add('forcal-colorpicker-popup-upside');
    } else {
      this.popup.classList.remove('forcal-colorpicker-popup-upside');
    }
    
    // Für Mobile-Ansicht: Scrolle zum ausgewählten Farbelement
    if (isMobileView) {
      const activeElement = this.popup.querySelector('.forcal-colorpicker-preset.active');
      if (activeElement) {
        setTimeout(() => {
          activeElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
      }
    }
  }
  
  hidePopup() {
    this.popup.style.display = 'none';
  }
}

// Diese Funktion wird über rex:ready initialisiert
function initForcalColorPicker() {
  // Alle ColorPicker-Elemente finden und initialisieren
  const colorPickerInputs = document.querySelectorAll('.forcal_colorpalette');
  colorPickerInputs.forEach(input => {
    new ForcalColorPicker(input, {
      clear_btn: 'last'
    });
  });
}

// jQuery-Adapter für die Kompatibilität mit dem bestehenden Code
if (typeof jQuery !== 'undefined') {
  jQuery.fn.paletteColorPicker = function(options) {
    return this.each(function() {
      // Prüfen, ob bereits ein ColorPicker für dieses Element initialisiert wurde
      const parent = jQuery(this).parent();
      if (parent.hasClass('forcal-colorpicker-wrapper') || parent.hasClass('palette-color-picker-button')) {
        // Element bereits initialisiert - aktualisiere ggf. nur die Farbe
        if (this._forcalColorPicker && this.value) {
          this._forcalColorPicker.updateColor(this.value, false);
        }
        return;
      }
      
      // Neuen ColorPicker initialisieren und in Instance speichern
      this._forcalColorPicker = new ForcalColorPicker(this, options);
    });
  };
}