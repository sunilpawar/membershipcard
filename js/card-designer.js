/**
 * Membership Card Designer
 * Drag and drop card designer with token support
 */
class MembershipCardDesigner {
  constructor() {
    this.canvas = null;
    this.selectedElement = null;
    this.tokens = {};
    this.gridSize = 10;
    this.snapToGrid = true;

    this.init();
  }

  init() {
    this.initCanvas();
    this.initToolbar();
    this.initTokenPanel();
    this.initPropertyPanel();
    this.bindEvents();
    this.loadTemplate();
  }

  initCanvas() {
    const canvasEl = document.getElementById('card-canvas');
    this.canvas = new fabric.Canvas(canvasEl, {
      width: 350,
      height: 220,
      backgroundColor: '#ffffff',
      preserveObjectStacking: true
    });

    // Enable object controls
    this.canvas.on('selection:created', (e) => this.onObjectSelected(e));
    this.canvas.on('selection:updated', (e) => this.onObjectSelected(e));
    this.canvas.on('selection:cleared', () => this.onObjectDeselected());
    this.canvas.on('object:modified', () => this.saveState());
    this.canvas.on('object:moving', (e) => this.snapToGridHandler(e));
  }

  initToolbar() {
    const toolbar = document.getElementById('card-toolbar');

    // Add text button
    const addTextBtn = document.createElement('button');
    addTextBtn.className = 'btn btn-primary';
    addTextBtn.innerHTML = '<i class="fa fa-font"></i> Add Text';
    addTextBtn.onclick = () => this.addText();
    toolbar.appendChild(addTextBtn);

    // Add image button
    const addImageBtn = document.createElement('button');
    addImageBtn.className = 'btn btn-primary';
    addImageBtn.innerHTML = '<i class="fa fa-image"></i> Add Image';
    addImageBtn.onclick = () => this.addImage();
    toolbar.appendChild(addImageBtn);

    // Add QR code button
    const addQrBtn = document.createElement('button');
    addQrBtn.className = 'btn btn-primary';
    addQrBtn.innerHTML = '<i class="fa fa-qrcode"></i> Add QR Code';
    addQrBtn.onclick = () => this.addQRCode();
    toolbar.appendChild(addQrBtn);

    // Add barcode button
    const addBarcodeBtn = document.createElement('button');
    addBarcodeBtn.className = 'btn btn-primary';
    addBarcodeBtn.innerHTML = '<i class="fa fa-barcode"></i> Add Barcode';
    addBarcodeBtn.onclick = () => this.addBarcode();
    toolbar.appendChild(addBarcodeBtn);

    // Delete button
    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn btn-danger';
    deleteBtn.id = 'delete-element';
    deleteBtn.innerHTML = '<i class="fa fa-trash"></i> Delete';
    deleteBtn.onclick = () => this.deleteSelected();
    deleteBtn.style.display = 'none';
    toolbar.appendChild(deleteBtn);

    // Grid toggle
    const gridToggle = document.createElement('button');
    gridToggle.className = 'btn btn-secondary';
    gridToggle.innerHTML = '<i class="fa fa-grid"></i> Grid';
    gridToggle.onclick = () => this.toggleGrid();
    toolbar.appendChild(gridToggle);
  }

  initTokenPanel() {
    const tokenPanel = document.getElementById('token-panel');
    const tokens = window.membershipTokens || {};

    Object.keys(tokens).forEach(category => {
      const categoryDiv = document.createElement('div');
      categoryDiv.className = 'token-category';

      const categoryTitle = document.createElement('h4');
      categoryTitle.textContent = category.replace('_', ' ').toUpperCase();
      categoryDiv.appendChild(categoryTitle);

      const tokenList = document.createElement('div');
      tokenList.className = 'token-list';

      Object.keys(tokens[category]).forEach(tokenKey => {
        const tokenDiv = document.createElement('div');
        tokenDiv.className = 'token-item';
        tokenDiv.draggable = true;
        tokenDiv.textContent = tokens[category][tokenKey];
        tokenDiv.dataset.token = `{${category}.${tokenKey}}`;

        tokenDiv.addEventListener('dragstart', (e) => {
          e.dataTransfer.setData('text/plain', tokenDiv.dataset.token);
          e.dataTransfer.setData('token-type', 'text');
        });

        tokenDiv.addEventListener('click', () => {
          this.insertToken(tokenDiv.dataset.token);
        });

        tokenList.appendChild(tokenDiv);
      });

      categoryDiv.appendChild(tokenList);
      tokenPanel.appendChild(categoryDiv);
    });
  }

  initPropertyPanel() {
    const propertyPanel = document.getElementById('property-panel');

    // Text properties
    const textProps = document.createElement('div');
    textProps.id = 'text-properties';
    textProps.style.display = 'none';
    textProps.innerHTML = `
      <h4>Text Properties</h4>
      <div class="form-group">
        <label>Text:</label>
        <input type="text" id="text-content" class="form-control">
      </div>
      <div class="form-group">
        <label>Font Size:</label>
        <input type="number" id="font-size" class="form-control" min="8" max="72" value="16">
      </div>
      <div class="form-group">
        <label>Font Family:</label>
        <select id="font-family" class="form-control">
          <option value="Arial">Arial</option>
          <option value="Helvetica">Helvetica</option>
          <option value="Times New Roman">Times New Roman</option>
          <option value="Courier New">Courier New</option>
        </select>
      </div>
      <div class="form-group">
        <label>Color:</label>
        <input type="color" id="text-color" class="form-control" value="#000000">
      </div>
      <div class="form-group">
        <label>Alignment:</label>
        <select id="text-align" class="form-control">
          <option value="left">Left</option>
          <option value="center">Center</option>
          <option value="right">Right</option>
        </select>
      </div>
    `;
    propertyPanel.appendChild(textProps);

    // Image properties
    const imageProps = document.createElement('div');
    imageProps.id = 'image-properties';
    imageProps.style.display = 'none';
    imageProps.innerHTML = `
      <h4>Image Properties</h4>
      <div class="form-group">
        <label>Width:</label>
        <input type="number" id="image-width" class="form-control" min="10" max="500">
      </div>
      <div class="form-group">
        <label>Height:</label>
        <input type="number" id="image-height" class="form-control" min="10" max="500">
      </div>
      <div class="form-group">
        <label>Border Radius:</label>
        <input type="number" id="image-radius" class="form-control" min="0" max="50" value="0">
      </div>
    `;
    propertyPanel.appendChild(imageProps);

    // Position properties (common)
    const positionProps = document.createElement('div');
    positionProps.id = 'position-properties';
    positionProps.style.display = 'none';
    positionProps.innerHTML = `
      <h4>Position</h4>
      <div class="form-group">
        <label>X Position:</label>
        <input type="number" id="pos-x" class="form-control">
      </div>
      <div class="form-group">
        <label>Y Position:</label>
        <input type="number" id="pos-y" class="form-control">
      </div>
      <div class="form-group">
        <label>Rotation:</label>
        <input type="number" id="rotation" class="form-control" min="-180" max="180" value="0">
      </div>
    `;
    propertyPanel.appendChild(positionProps);

    this.bindPropertyEvents();
  }

  bindPropertyEvents() {
    // Text property events
    document.getElementById('text-content').addEventListener('input', (e) => {
      if (this.selectedElement && this.selectedElement.type === 'text') {
        this.selectedElement.set('text', e.target.value);
        this.canvas.renderAll();
        this.saveState();
      }
    });

    document.getElementById('font-size').addEventListener('input', (e) => {
      if (this.selectedElement && this.selectedElement.type === 'text') {
        this.selectedElement.set('fontSize', parseInt(e.target.value));
        this.canvas.renderAll();
        this.saveState();
      }
    });

    document.getElementById('font-family').addEventListener('change', (e) => {
      if (this.selectedElement && this.selectedElement.type === 'text') {
        this.selectedElement.set('fontFamily', e.target.value);
        this.canvas.renderAll();
        this.saveState();
      }
    });

    document.getElementById('text-color').addEventListener('input', (e) => {
      if (this.selectedElement && this.selectedElement.type === 'text') {
        this.selectedElement.set('fill', e.target.value);
        this.canvas.renderAll();
        this.saveState();
      }
    });

    // Position property events
    document.getElementById('pos-x').addEventListener('input', (e) => {
      if (this.selectedElement) {
        this.selectedElement.set('left', parseInt(e.target.value));
        this.canvas.renderAll();
        this.saveState();
      }
    });

    document.getElementById('pos-y').addEventListener('input', (e) => {
      if (this.selectedElement) {
        this.selectedElement.set('top', parseInt(e.target.value));
        this.canvas.renderAll();
        this.saveState();
      }
    });
  }

  bindEvents() {
    // Canvas drop support
    const canvasContainer = document.querySelector('.canvas-container');

    canvasContainer.addEventListener('dragover', (e) => {
      e.preventDefault();
    });

    canvasContainer.addEventListener('drop', (e) => {
      e.preventDefault();
      const token = e.dataTransfer.getData('text/plain');
      const tokenType = e.dataTransfer.getData('token-type');

      if (token) {
        const pointer = this.canvas.getPointer(e);
        this.addTokenToCanvas(token, pointer.x, pointer.y, tokenType);
      }
    });

    // Save button
    document.getElementById('save-template').addEventListener('click', () => {
      this.saveTemplate();
    });

    // Preview button
    document.getElementById('preview-card').addEventListener('click', () => {
      this.previewCard();
    });
  }

  addText() {
    const text = new fabric.Text('Sample Text', {
      left: 50,
      top: 50,
      fontSize: 16,
      fill: '#000000',
      fontFamily: 'Arial'
    });

    this.canvas.add(text);
    this.canvas.setActiveObject(text);
    this.saveState();
  }

  addImage() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';

    input.onchange = (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
          fabric.Image.fromURL(event.target.result, (img) => {
            img.set({
              left: 50,
              top: 50,
              scaleX: 0.5,
              scaleY: 0.5
            });
            this.canvas.add(img);
            this.canvas.setActiveObject(img);
            this.saveState();
          });
        };
        reader.readAsDataURL(file);
      }
    };

    input.click();
  }

  addQRCode() {
    const qrCode = new fabric.Rect({
      left: 50,
      top: 50,
      width: 80,
      height: 80,
      fill: '#000000',
      stroke: '#cccccc',
      strokeWidth: 1
    });

    const qrText = new fabric.Text('QR', {
      left: 75,
      top: 75,
      fontSize: 12,
      fill: '#ffffff',
      fontFamily: 'Arial',
      originX: 'center',
      originY: 'center'
    });

    const group = new fabric.Group([qrCode, qrText], {
      left: 50,
      top: 50
    });

    group.set('elementType', 'qrcode');
    this.canvas.add(group);
    this.canvas.setActiveObject(group);
    this.saveState();
  }

  addBarcode() {
    const barcode = new fabric.Rect({
      left: 50,
      top: 50,
      width: 120,
      height: 40,
      fill: '#000000',
      stroke: '#cccccc',
      strokeWidth: 1
    });

    const barcodeText = new fabric.Text('||||||||||', {
      left: 110,
      top: 70,
      fontSize: 20,
      fill: '#ffffff',
      fontFamily: 'Courier New',
      originX: 'center',
      originY: 'center'
    });

    const group = new fabric.Group([barcode, barcodeText], {
      left: 50,
      top: 50
    });

    group.set('elementType', 'barcode');
    this.canvas.add(group);
    this.canvas.setActiveObject(group);
    this.saveState();
  }

  addTokenToCanvas(token, x, y, tokenType = 'text') {
    if (tokenType === 'text' || !tokenType) {
      const text = new fabric.Text(token, {
        left: x,
        top: y,
        fontSize: 14,
        fill: '#000000',
        fontFamily: 'Arial'
      });

      text.set('tokenValue', token);
      this.canvas.add(text);
      this.canvas.setActiveObject(text);
    }

    this.saveState();
  }

  insertToken(token) {
    if (this.selectedElement && this.selectedElement.type === 'text') {
      const currentText = this.selectedElement.text || '';
      this.selectedElement.set('text', currentText + ' ' + token);
      this.canvas.renderAll();
      this.saveState();
    } else {
      this.addTokenToCanvas(token, 50, 50);
    }
  }

  onObjectSelected(e) {
    this.selectedElement = e.target || e.selected[0];
    this.updatePropertyPanel();
    document.getElementById('delete-element').style.display = 'inline-block';
  }

  onObjectDeselected() {
    this.selectedElement = null;
    this.hidePropertyPanels();
    document.getElementById('delete-element').style.display = 'none';
  }

  updatePropertyPanel() {
    this.hidePropertyPanels();

    if (!this.selectedElement) return;

    // Show position properties for all elements
    document.getElementById('position-properties').style.display = 'block';
    document.getElementById('pos-x').value = Math.round(this.selectedElement.left);
    document.getElementById('pos-y').value = Math.round(this.selectedElement.top);
    document.getElementById('rotation').value = Math.round(this.selectedElement.angle || 0);

    if (this.selectedElement.type === 'text') {
      document.getElementById('text-properties').style.display = 'block';
      document.getElementById('text-content').value = this.selectedElement.text;
      document.getElementById('font-size').value = this.selectedElement.fontSize;
      document.getElementById('font-family').value = this.selectedElement.fontFamily;
      document.getElementById('text-color').value = this.selectedElement.fill;
    } else if (this.selectedElement.type === 'image') {
      document.getElementById('image-properties').style.display = 'block';
      document.getElementById('image-width').value = Math.round(this.selectedElement.width * this.selectedElement.scaleX);
      document.getElementById('image-height').value = Math.round(this.selectedElement.height * this.selectedElement.scaleY);
    }
  }

  hidePropertyPanels() {
    document.getElementById('text-properties').style.display = 'none';
    document.getElementById('image-properties').style.display = 'none';
    document.getElementById('position-properties').style.display = 'none';
  }

  deleteSelected() {
    if (this.selectedElement) {
      this.canvas.remove(this.selectedElement);
      this.selectedElement = null;
      this.hidePropertyPanels();
      document.getElementById('delete-element').style.display = 'none';
      this.saveState();
    }
  }

  toggleGrid() {
    this.snapToGrid = !this.snapToGrid;
  }

  snapToGridHandler(e) {
    if (!this.snapToGrid) return;

    const obj = e.target;
    obj.set({
      left: Math.round(obj.left / this.gridSize) * this.gridSize,
      top: Math.round(obj.top / this.gridSize) * this.gridSize
    });
  }

  saveState() {
    // Auto-save canvas state
    const canvasData = this.canvas.toJSON(['tokenValue', 'elementType']);
    localStorage.setItem('membershipcard_canvas_state', JSON.stringify(canvasData));
  }

  loadTemplate() {
    // Load template data if editing existing template
    const templateData = window.templateData;
    if (templateData && templateData.elements) {
      this.canvas.loadFromJSON(templateData.elements, () => {
        this.canvas.renderAll();
      });

      // Set canvas dimensions
      if (templateData.card_width && templateData.card_height) {
        this.canvas.setWidth(templateData.card_width);
        this.canvas.setHeight(templateData.card_height);
      }

      // Set background
      if (templateData.background_color) {
        this.canvas.setBackgroundColor(templateData.background_color, this.canvas.renderAll.bind(this.canvas));
      }
    }
  }

  saveTemplate() {
    const templateName = document.getElementById('template-name').value;
    if (!templateName) {
      alert('Please enter a template name');
      return;
    }

    const canvasData = this.canvas.toJSON(['tokenValue', 'elementType']);

    const templateData = {
      name: templateName,
      description: document.getElementById('template-description').value,
      card_width: this.canvas.width,
      card_height: this.canvas.height,
      background_color: this.canvas.backgroundColor,
      elements: JSON.stringify(canvasData),
      is_active: 1
    };

    // Send AJAX request to save template
    CRM.api3('MembershipCardTemplate', 'create', templateData)
      .done(function(result) {
        CRM.alert('Template saved successfully!', 'Success', 'success');
        window.location.href = CRM.url('civicrm/membership-card-templates');
      })
      .fail(function(error) {
        CRM.alert('Error saving template: ' + error.error_message, 'Error', 'error');
      });
  }

  previewCard() {
    // Generate preview with sample data
    const sampleData = {
      'contact.display_name': 'John Doe',
      'contact.first_name': 'John',
      'contact.last_name': 'Doe',
      'contact.email': 'john.doe@example.com',
      'membership.membership_type': 'Gold Membership',
      'membership.status': 'Current',
      'membership.start_date': '2024-01-01',
      'membership.end_date': '2024-12-31',
      'membership.membership_id': 'M12345',
      'organization.organization_name': 'Example Organization'
    };

    this.renderCardWithData(sampleData);
  }

  renderCardWithData(data) {
    // Clone canvas for preview
    const previewCanvas = new fabric.Canvas();
    previewCanvas.loadFromJSON(this.canvas.toJSON(['tokenValue', 'elementType']), () => {
      // Replace tokens with actual data
      previewCanvas.forEachObject((obj) => {
        if (obj.type === 'text' && obj.tokenValue) {
          const tokenPattern = /\{([^}]+)\}/g;
          let text = obj.text;
          let match;

          while ((match = tokenPattern.exec(obj.text)) !== null) {
            const token = match[1];
            const value = data[token] || match[0]; // Keep original if no data
            text = text.replace(match[0], value);
          }

          obj.set('text', text);
        }
      });

      // Generate preview image
      const dataURL = previewCanvas.toDataURL('image/png');
      this.showPreviewModal(dataURL);
    });
  }

  showPreviewModal(imageData) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Card Preview</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body text-center">
            <img src="${imageData}" class="img-responsive" style="max-width: 100%; height: auto;">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    `;

    document.body.appendChild(modal);
    $(modal).modal('show');

    // Remove modal when hidden
    $(modal).on('hidden.bs.modal', function() {
      document.body.removeChild(modal);
    });
  }

  exportCard(format = 'png') {
    const dataURL = this.canvas.toDataURL(`image/${format}`);

    // Create download link
    const link = document.createElement('a');
    link.download = `membership-card.${format}`;
    link.href = dataURL;
    link.click();
  }

  setCanvasSize(width, height) {
    this.canvas.setWidth(width);
    this.canvas.setHeight(height);
    this.canvas.renderAll();
    this.saveState();
  }

  setBackgroundColor(color) {
    this.canvas.setBackgroundColor(color, this.canvas.renderAll.bind(this.canvas));
    this.saveState();
  }

  setBackgroundImage(imageUrl) {
    fabric.Image.fromURL(imageUrl, (img) => {
      img.set({
        scaleX: this.canvas.width / img.width,
        scaleY: this.canvas.height / img.height,
        selectable: false,
        evented: false
      });

      this.canvas.setBackgroundImage(img, this.canvas.renderAll.bind(this.canvas));
      this.saveState();
    });
  }
}

// Initialize designer when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  if (document.getElementById('card-canvas')) {
    window.cardDesigner = new MembershipCardDesigner();
  }
});

// Utility functions for card generation
class MembershipCardGenerator {
  static generateCard(membershipId, templateId) {
    return new Promise((resolve, reject) => {
      // Get membership data
      CRM.api3('Membership', 'get', {
        sequential: 1,
        id: membershipId,
        api: {
          Contact: ['get', {id: '$value.contact_id'}],
          MembershipType: ['get', {id: '$value.membership_type_id'}]
        }
      }).done(function (membershipResult) {
        if (membershipResult.values.length === 0) {
          reject('Membership not found');
          return;
        }

        const membership = membershipResult.values[0];
        const contact = membership['api.Contact.get'].values[0];
        const membershipType = membership['api.MembershipType.get'].values[0];

        // Get template
        CRM.api3('MembershipCardTemplate', 'get', {
          sequential: 1,
          id: templateId
        }).done(function (templateResult) {
          if (templateResult.values.length === 0) {
            reject('Template not found');
            return;
          }

          const template = templateResult.values[0];
          const cardData = MembershipCardGenerator.processTemplate(template, contact, membership, membershipType);

          resolve(cardData);
        }).fail(function (error) {
          reject(error.error_message);
        });

      }).fail(function (error) {
        reject(error.error_message);
      });
    });
  }

  static processTemplate(template, contact, membership, membershipType) {
    const tokenData = {
      'contact.display_name': contact.display_name,
      'contact.first_name': contact.first_name,
      'contact.last_name': contact.last_name,
      'contact.email': contact.email,
      'contact.phone': contact.phone,
      'contact.street_address': contact.street_address,
      'contact.city': contact.city,
      'contact.state_province': contact.state_province_name,
      'contact.postal_code': contact.postal_code,
      'contact.image_URL': contact.image_URL,
      'membership.membership_type': membershipType.name,
      'membership.status': membership.status_id,
      'membership.start_date': membership.start_date,
      'membership.end_date': membership.end_date,
      'membership.join_date': membership.join_date,
      'membership.membership_id': membership.id,
      'membership.source': membership.source,
      'system.current_date': new Date().toLocaleDateString(),
      'system.qr_code': `MEMBER:${membership.id}`,
      'system.barcode': membership.id.toString().padStart(12, '0')
    };

    // Process template elements
    const elements = JSON.parse(template.elements);

    // Replace tokens in text elements
    if (elements.objects) {
      elements.objects.forEach(obj => {
        if (obj.type === 'text' && obj.tokenValue) {
          const tokenPattern = /\{([^}]+)\}/g;
          let text = obj.text;
          let match;

          while ((match = tokenPattern.exec(obj.text)) !== null) {
            const token = match[1];
            const value = tokenData[token] || match[0];
            text = text.replace(match[0], value);
          }

          obj.text = text;
        }
      });
    }

    return {
      template: template,
      elements: elements,
      tokenData: tokenData,
      membership: membership,
      contact: contact
    };
  }

  static generateQRCode(data) {
    // This would integrate with a QR code library
    // For now, return placeholder
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';
  }

  static generateBarcode(data) {
    // This would integrate with a barcode library
    // For now, return placeholder
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';
  }
}
