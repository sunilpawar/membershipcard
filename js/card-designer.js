/**
 * Membership Card Designer - Enhanced with Dual-Sided Support
 */
class MembershipCardDesigner {
  constructor() {
    this.canvas = null;
    this.selectedElement = null;
    this.tokens = {};
    this.gridSize = 10;
    this.snapToGrid = true;

    // Dual-sided support
    this.currentSide = 'front';
    this.cardData = {
      front: {
        elements: null,
        background_color: '#ffffff',
        background_image: null
      },
      back: {
        elements: null,
        background_color: '#ffffff',
        background_image: null
      }
    };
    this.isDualSided = false;

    this.init();
  }

  init() {
    this.initCanvas();
    this.initToolbar();
    this.initSideToggle();
    this.initTokenPanel();
    this.initPropertyPanel();
    this.bindEvents();
    this.loadTemplate();
  }

  initSideToggle() {
    const toolbar = document.getElementById('card-toolbar');

    // Create side toggle container
    const sideToggleContainer = document.createElement('div');
    sideToggleContainer.className = 'side-toggle-container';
    sideToggleContainer.innerHTML = `
      <div class="side-toggle-group">
        <label class="side-toggle-label">Card Side:</label>
        <div class="btn-group side-toggle-buttons" role="group">
          <button type="button" class="btn btn-primary active" id="front-side-btn" data-side="front">
            <i class="fa fa-credit-card"></i> Front Side
          </button>
          <button type="button" class="btn btn-outline-primary" id="back-side-btn" data-side="back">
            <i class="fa fa-credit-card fa-flip-horizontal"></i> Back Side
          </button>
        </div>
        <div class="side-indicator">
          <span class="current-side-text">Currently editing: <strong>Front Side</strong></span>
        </div>
      </div>
    `;

    // Insert at the beginning of toolbar
    toolbar.insertBefore(sideToggleContainer, toolbar.firstChild);

    // Bind side toggle events
    document.getElementById('front-side-btn').addEventListener('click', () => this.switchSide('front'));
    document.getElementById('back-side-btn').addEventListener('click', () => this.switchSide('back'));
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

    // Copy to other side button
    const copyToOtherSideBtn = document.createElement('button');
    copyToOtherSideBtn.className = 'btn btn-info';
    copyToOtherSideBtn.innerHTML = '<i class="fa fa-copy"></i> Copy to Other Side';
    copyToOtherSideBtn.onclick = () => this.copyToOtherSide();
    toolbar.appendChild(copyToOtherSideBtn);

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

    // Preview both sides button
    const previewBothBtn = document.createElement('button');
    previewBothBtn.className = 'btn btn-success';
    previewBothBtn.innerHTML = '<i class="fa fa-eye"></i> Preview Both Sides';
    previewBothBtn.onclick = () => this.previewBothSides();
    toolbar.appendChild(previewBothBtn);
  }

  // Dual-sided functionality
  switchSide(side) {
    if (this.currentSide === side) return;

    // Save current side data
    this.saveCurrentSideData();

    // Switch to new side
    this.currentSide = side;

    // Update UI
    this.updateSideToggleUI();

    // Load new side data
    this.loadSideData();

    // Clear selection
    this.canvas.discardActiveObject();
    this.onObjectDeselected();

    // Update token recommendations
    this.updateTokenRecommendations();

    console.log(`Switched to ${side} side`);
  }

  updateSideToggleUI() {
    // Update button states
    document.getElementById('front-side-btn').className = this.currentSide === 'front'
      ? 'btn btn-primary active'
      : 'btn btn-outline-primary';

    document.getElementById('back-side-btn').className = this.currentSide === 'back'
      ? 'btn btn-primary active'
      : 'btn btn-outline-primary';

    // Update indicator text
    const sideText = this.currentSide === 'front' ? 'Front Side' : 'Back Side';
    document.querySelector('.current-side-text').innerHTML = `Currently editing: <strong>${sideText}</strong>`;

    // Update canvas background for current side
    const bgColor = this.cardData[this.currentSide].background_color || '#ffffff';
    this.canvas.setBackgroundColor(bgColor, this.canvas.renderAll.bind(this.canvas));
  }

  saveCurrentSideData() {
    // Save canvas state for current side
    const canvasData = this.canvas.toJSON(['tokenValue', 'elementType', 'isToken', 'cardSide']);
    this.cardData[this.currentSide].elements = canvasData;
    this.cardData[this.currentSide].background_color = this.canvas.backgroundColor || '#ffffff';

    console.log(`Saved ${this.currentSide} side data:`, this.cardData[this.currentSide]);
  }

  loadSideData() {
    // Clear canvas
    this.canvas.clear();

    // Load side-specific data
    const sideData = this.cardData[this.currentSide];

    if (sideData.elements) {
      this.canvas.loadFromJSON(sideData.elements, () => {
        // Mark all objects with current side
        this.canvas.forEachObject((obj) => {
          obj.set('cardSide', this.currentSide);
        });
        this.canvas.renderAll();
      });
    }

    // Set background
    const bgColor = sideData.background_color || '#ffffff';
    this.canvas.setBackgroundColor(bgColor, this.canvas.renderAll.bind(this.canvas));

    // Set background image if exists
    if (sideData.background_image) {
      this.setBackgroundImage(sideData.background_image);
    }
  }

  copyToOtherSide() {
    if (!this.selectedElement) {
      // Copy entire canvas to other side
      const otherSide = this.currentSide === 'front' ? 'back' : 'front';
      const currentData = this.canvas.toJSON(['tokenValue', 'elementType', 'isToken', 'cardSide']);

      // Store current canvas data to other side
      this.cardData[otherSide].elements = currentData;
      this.cardData[otherSide].background_color = this.canvas.backgroundColor;

      if (typeof CRM !== 'undefined') {
        CRM.alert(`All elements copied to ${otherSide} side. Switch to ${otherSide} side to see them.`, 'Success', 'success');
      } else {
        alert(`All elements copied to ${otherSide} side. Switch to ${otherSide} side to see them.`);
      }
    } else {
      // Copy selected element to other side
      const otherSide = this.currentSide === 'front' ? 'back' : 'front';

      this.selectedElement.clone((cloned) => {
        cloned.set('cardSide', otherSide);

        // Store the cloned element for the other side
        if (!this.cardData[otherSide].pendingElements) {
          this.cardData[otherSide].pendingElements = [];
        }
        this.cardData[otherSide].pendingElements.push(cloned.toObject(['tokenValue', 'elementType', 'isToken', 'cardSide']));

        if (typeof CRM !== 'undefined') {
          CRM.alert(`Selected element copied to ${otherSide} side. Switch to ${otherSide} side to see it.`, 'Success', 'success');
        } else {
          alert(`Selected element copied to ${otherSide} side. Switch to ${otherSide} side to see it.`);
        }
      });
    }
  }

  // Enhanced add methods with side awareness
  addText() {
    const text = new fabric.Text('Sample Text', {
      left: 50,
      top: 50,
      fontSize: 16,
      fill: '#000000',
      fontFamily: 'Arial',
      cardSide: this.currentSide
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
              scaleY: 0.5,
              cardSide: this.currentSide
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
      strokeWidth: 1,
      cardSide: this.currentSide
    });

    const qrText = new fabric.Text('QR', {
      left: 75,
      top: 75,
      fontSize: 12,
      fill: '#ffffff',
      fontFamily: 'Arial',
      originX: 'center',
      originY: 'center',
      cardSide: this.currentSide
    });

    const group = new fabric.Group([qrCode, qrText], {
      left: 50,
      top: 50,
      cardSide: this.currentSide
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
      strokeWidth: 1,
      cardSide: this.currentSide
    });

    const barcodeText = new fabric.Text('||||||||||', {
      left: 110,
      top: 70,
      fontSize: 20,
      fill: '#ffffff',
      fontFamily: 'Courier New',
      originX: 'center',
      originY: 'center',
      cardSide: this.currentSide
    });

    const group = new fabric.Group([barcode, barcodeText], {
      left: 50,
      top: 50,
      cardSide: this.currentSide
    });

    group.set('elementType', 'barcode');
    this.canvas.add(group);
    this.canvas.setActiveObject(group);
    this.saveState();
  }

  // Enhanced preview for both sides
  previewBothSides() {
    // Save current side first
    this.saveCurrentSideData();

    // Generate both sides
    const frontData = this.generateSidePreview('front');
    const backData = this.generateSidePreview('back');

    this.showDualSidePreview(frontData, backData);
  }

  generateSidePreview(side) {
    // Create temporary canvas for preview
    const tempCanvas = new fabric.Canvas();
    tempCanvas.setWidth(this.canvas.width);
    tempCanvas.setHeight(this.canvas.height);

    const sideData = this.cardData[side];
    tempCanvas.setBackgroundColor(sideData.background_color || '#ffffff');

    if (sideData.elements) {
      tempCanvas.loadFromJSON(sideData.elements, () => {
        // Replace tokens with sample data
        tempCanvas.forEachObject((obj) => {
          if (obj.type === 'text' && (obj.tokenValue || obj.isToken)) {
            this.replaceTokensInText(obj);
          }
        });
        tempCanvas.renderAll();
      });
    }

    return tempCanvas.toDataURL('image/png');
  }

  showDualSidePreview(frontImageData, backImageData) {
    const modal = document.createElement('div');
    modal.className = 'modal fade dual-side-preview-modal';
    modal.innerHTML = `
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">
              <i class="fa fa-eye"></i> Membership Card Preview - Both Sides
            </h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="dual-card-preview">
              <div class="card-side-preview">
                <h5><i class="fa fa-credit-card"></i> Front Side</h5>
                <div class="card-preview-container">
                  <img src="${frontImageData}" class="card-preview-image" alt="Front Side">
                </div>
              </div>
              <div class="card-side-preview">
                <h5><i class="fa fa-credit-card fa-flip-horizontal"></i> Back Side</h5>
                <div class="card-preview-container">
                  <img src="${backImageData}" class="card-preview-image" alt="Back Side">
                </div>
              </div>
            </div>
            <div class="preview-info">
              <p class="text-muted">
                <i class="fa fa-info-circle"></i>
                Preview generated with sample data. Actual cards will show real member information.
              </p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="window.print()">
              <i class="fa fa-print"></i> Print Both Sides
            </button>
            <button type="button" class="btn btn-info" onclick="downloadBothSides('${frontImageData}', '${backImageData}')">
              <i class="fa fa-download"></i> Download Both
            </button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              <i class="fa fa-times"></i> Close
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    // Show modal
    if (typeof $ !== 'undefined' && $.fn.modal) {
      $(modal).modal('show');
      $(modal).on('hidden.bs.modal', function() {
        document.body.removeChild(modal);
      });
    }
  }

  // Enhanced token panel with side recommendations
  initTokenPanel() {
    const tokenPanel = document.getElementById('token-panel');
    const tokens = window.membershipTokens || this.getDefaultTokens();

    // Clear existing content
    tokenPanel.innerHTML = '<h3>Available Tokens</h3><p class="text-muted small">Drag tokens to the card or click to insert</p>';

    // Add side recommendations
    const recommendationsDiv = document.createElement('div');
    recommendationsDiv.className = 'side-recommendations';
    recommendationsDiv.innerHTML = `
      <h5>Recommended for ${this.currentSide === 'front' ? 'Front' : 'Back'} Side:</h5>
      <div class="recommended-tokens" id="recommended-tokens">
        ${this.getRecommendedTokensHTML()}
      </div>
    `;
    tokenPanel.appendChild(recommendationsDiv);

    // Add all token categories
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
        tokenDiv.dataset.tokenType = 'text';

        this.bindTokenEvents(tokenDiv);
        tokenList.appendChild(tokenDiv);
      });

      categoryDiv.appendChild(tokenList);
      tokenPanel.appendChild(categoryDiv);
    });
  }

  getRecommendedTokensHTML() {
    const frontTokens = [
      '{contact.display_name}',
      '{contact.image_URL}',
      '{membership.membership_type}',
      '{organization.organization_name}'
    ];

    const backTokens = [
      '{membership.membership_id}',
      '{membership.end_date}',
      '{system.qr_code}',
      '{system.barcode}',
      '{contact.email}',
      '{contact.phone}'
    ];

    const recommendedTokens = this.currentSide === 'front' ? frontTokens : backTokens;

    return recommendedTokens.map(token =>
      `<div class="token-item recommended" draggable="true" data-token="${token}" data-token-type="text">
        ${token.replace(/[{}]/g, '').replace(/\./g, ' ').replace(/_/g, ' ')}
      </div>`
    ).join('');
  }

  updateTokenRecommendations() {
    const sideText = this.currentSide === 'front' ? 'Front' : 'Back';
    const recommendationsHeader = document.querySelector('.side-recommendations h5');
    if (recommendationsHeader) {
      recommendationsHeader.textContent = `Recommended for ${sideText} Side:`;
    }

    const recommendedContainer = document.getElementById('recommended-tokens');
    if (recommendedContainer) {
      recommendedContainer.innerHTML = this.getRecommendedTokensHTML();

      // Bind events to new recommended tokens
      recommendedContainer.querySelectorAll('.token-item').forEach(tokenDiv => {
        this.bindTokenEvents(tokenDiv);
      });
    }
  }

  bindTokenEvents(tokenDiv) {
    // Drag events
    tokenDiv.addEventListener('dragstart', (e) => {
      e.dataTransfer.setData('text/plain', tokenDiv.dataset.token);
      e.dataTransfer.setData('token-type', 'text');
      e.dataTransfer.effectAllowed = 'copy';
      tokenDiv.style.opacity = '0.5';
    });

    tokenDiv.addEventListener('dragend', (e) => {
      tokenDiv.style.opacity = '1';
    });

    // Click events
    tokenDiv.addEventListener('click', (e) => {
      e.preventDefault();
      this.insertToken(tokenDiv.dataset.token);
    });
  }

  // Enhanced save state for dual-sided
  saveState() {
    this.saveCurrentSideData();

    // Save complete template state
    const templateData = {
      card_width: this.canvas.width,
      card_height: this.canvas.height,
      front_side: this.cardData.front,
      back_side: this.cardData.back,
      current_side: this.currentSide,
      is_dual_sided: this.isDualSided
    };

    localStorage.setItem('membershipcard_dual_template', JSON.stringify(templateData));
  }

  // Enhanced load template for dual-sided
  loadTemplate() {
    const templateData = window.templateData;

    if (templateData) {
      // Check if it's a dual-sided template
      if (templateData.front_side || templateData.back_side || templateData.is_dual_sided) {
        this.isDualSided = true;
        this.cardData.front = templateData.front_side || this.cardData.front;
        this.cardData.back = templateData.back_side || this.cardData.back;
      } else if (templateData.elements) {
        // Legacy single-side template - load to front side
        this.cardData.front.elements = templateData.elements;
        this.cardData.front.background_color = templateData.background_color;
      }

      if (templateData.card_width && templateData.card_height) {
        this.canvas.setWidth(templateData.card_width);
        this.canvas.setHeight(templateData.card_height);
      }
    } else {
      // Try to load from localStorage
      const savedState = localStorage.getItem('membershipcard_dual_template');
      if (savedState) {
        try {
          const parsed = JSON.parse(savedState);
          if (parsed.front_side) {
            this.cardData = {
              front: parsed.front_side,
              back: parsed.back_side
            };
            this.isDualSided = parsed.is_dual_sided || false;
          }

          if (parsed.current_side) {
            this.currentSide = parsed.current_side;
          }
        } catch (e) {
          console.log('Could not load saved dual-side state:', e);
        }
      }
    }

    // Load current side
    this.loadSideData();
    this.updateSideToggleUI();
    this.updateTokenRecommendations();
  }

  // Enhanced save template for dual-sided
  saveTemplate() {
    // Save current side before saving template
    this.saveCurrentSideData();

    const templateName = document.getElementById('template-name').value;
    if (!templateName) {
      alert('Please enter a template name');
      return;
    }

    const templateData = {
      id: document.getElementById('template-id').value,
      name: templateName,
      description: document.getElementById('template-description').value,
      card_width: this.canvas.width,
      card_height: this.canvas.height,
      // Save both sides
      front_elements: JSON.stringify(this.cardData.front.elements || {}),
      back_elements: JSON.stringify(this.cardData.back.elements || {}),
      front_background_color: this.cardData.front.background_color,
      back_background_color: this.cardData.back.background_color,
      front_background_image: this.cardData.front.background_image,
      back_background_image: this.cardData.back.background_image,
      is_dual_sided: this.isDualSided ? 1 : 0,
      // Legacy support - save front side as main elements
      elements: JSON.stringify(this.cardData.front.elements || {}),
      background_color: this.cardData.front.background_color,
      is_active: 1
    };

    console.log('Saving dual-sided template:', templateData);

    // Save via API
    if (typeof CRM !== 'undefined' && CRM.api3) {
      CRM.api3('MembershipCardTemplate', 'create', templateData)
        .done(function(result) {
          CRM.alert('Template saved successfully!', 'Success', 'success');
          //window.location.href = CRM.url('civicrm/membership-card-templates', {id: result.id});
        })
        .fail(function(error) {
          CRM.alert('Error saving template: ' + error.error_message, 'Error', 'error');
        });
    } else {
      console.log('Template would be saved:', templateData);
      alert('Template saved successfully! (Demo mode)');
    }
  }

  // Rest of the existing methods remain the same...
  // (getDefaultTokens, initPropertyPanel, onObjectSelected, etc.)

  getDefaultTokens() {
    return {
      contact: {
        display_name: 'Full Name',
        first_name: 'First Name',
        last_name: 'Last Name',
        email: 'Email Address',
        phone: 'Phone Number'
      },
      membership: {
        membership_type: 'Membership Type',
        status: 'Status',
        start_date: 'Start Date',
        end_date: 'End Date',
        membership_id: 'Membership ID'
      },
      organization: {
        organization_name: 'Organization Name'
      },
      system: {
        current_date: 'Current Date',
        qr_code: 'QR Code',
        barcode: 'Barcode'
      }
    };
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
        <textarea id="text-content" class="form-control" rows="3"></textarea>
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
    const canvas = this.canvas;

    if (canvasContainer) {
      canvasContainer.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';

        // Add visual feedback
        canvasContainer.style.backgroundColor = '#e3f2fd';
      });

      canvasContainer.addEventListener('dragleave', (e) => {
        // Remove visual feedback when leaving drop zone
        if (!canvasContainer.contains(e.relatedTarget)) {
          canvasContainer.style.backgroundColor = '';
        }
      });

      canvasContainer.addEventListener('drop', (e) => {
        e.preventDefault();

        // Remove visual feedback
        canvasContainer.style.backgroundColor = '';

        const token = e.dataTransfer.getData('text/plain');
        const tokenType = e.dataTransfer.getData('token-type');

        if (token) {
          // Get the correct position relative to the canvas
          const canvasElement = document.getElementById('card-canvas');
          const rect = canvasElement.getBoundingClientRect();

          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;

          // Ensure the drop position is within canvas bounds
          if (x >= 0 && x <= canvas.width && y >= 0 && y <= canvas.height) {
            this.addTokenToCanvas(token, x, y, tokenType);
          } else {
            // If dropped outside canvas, add at default position
            this.addTokenToCanvas(token, 50, 50, tokenType);
          }
        }
      });
    }

    // Save button
    const saveBtn = document.getElementById('save-template');
    if (saveBtn) {
      saveBtn.addEventListener('click', () => {
        this.saveTemplate();
      });
    }

    // Preview button
    const previewBtn = document.getElementById('preview-card');
    if (previewBtn) {
      previewBtn.addEventListener('click', () => {
        this.previewCard();
      });
    }
  }

  addTokenToCanvas(token, x, y, tokenType = 'text') {
    console.log('Adding token to canvas:', token, 'at position:', x, y);

    if (tokenType === 'text' || !tokenType) {
      const text = new fabric.Text(token, {
        left: x,
        top: y,
        fontSize: 14,
        fill: '#000000',
        fontFamily: 'Arial',
        cardSide: this.currentSide
      });

      // Store the original token for later replacement
      text.set('tokenValue', token);
      text.set('isToken', true);

      this.canvas.add(text);
      this.canvas.setActiveObject(text);
      this.canvas.renderAll();

      console.log('Token added successfully');
    }

    this.saveState();
  }

  insertToken(token) {
    console.log('Inserting token:', token);

    if (this.selectedElement && this.selectedElement.type === 'text') {
      // If text element is selected, append token to existing text
      const currentText = this.selectedElement.text || '';
      const newText = currentText.trim() + (currentText.trim() ? ' ' : '') + token;

      this.selectedElement.set('text', newText);

      // Update token value to include the new token
      const currentTokenValue = this.selectedElement.tokenValue || currentText;
      this.selectedElement.set('tokenValue', currentTokenValue + (currentTokenValue ? ' ' : '') + token);
      this.selectedElement.set('isToken', true);

      this.canvas.renderAll();

      // Update the property panel
      const textContentEl = document.getElementById('text-content');
      if (textContentEl) {
        textContentEl.value = newText;
      }

      console.log('Token inserted into selected text element');
    } else {
      // No text element selected, create new one
      this.addTokenToCanvas(token, 50, 50);
      console.log('Token added as new text element');
    }

    this.saveState();
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
    const gridBtn = document.querySelector('.btn:has(i.fa-grid)');
    if (gridBtn) {
      gridBtn.classList.toggle('active', this.snapToGrid);
    }
  }

  snapToGridHandler(e) {
    if (!this.snapToGrid) return;

    const obj = e.target;
    obj.set({
      left: Math.round(obj.left / this.gridSize) * this.gridSize,
      top: Math.round(obj.top / this.gridSize) * this.gridSize
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
      'organization.organization_name': 'Example Organization',
      'system.current_date': new Date().toLocaleDateString(),
      'system.qr_code': 'QR-CODE-PLACEHOLDER',
      'system.barcode': '123456789012'
    };

    this.renderCardWithData(sampleData);
  }

  renderCardWithData(data) {
    const canvasData = this.canvas.toJSON(['tokenValue', 'elementType', 'isToken', 'cardSide']);

    // Create temporary canvas for preview
    const tempCanvas = new fabric.Canvas();
    tempCanvas.setWidth(this.canvas.width);
    tempCanvas.setHeight(this.canvas.height);
    tempCanvas.setBackgroundColor(this.canvas.backgroundColor);

    tempCanvas.loadFromJSON(canvasData, () => {
      // Replace tokens with actual data
      tempCanvas.forEachObject((obj) => {
        if (obj.type === 'text' && (obj.tokenValue || obj.isToken)) {
          this.replaceTokensInText(obj, data);
        }
      });

      tempCanvas.renderAll();

      // Generate preview image
      const dataURL = tempCanvas.toDataURL('image/png');
      this.showPreviewModal(dataURL);

      // Clean up
      tempCanvas.dispose();
    });
  }

  replaceTokensInText(textObj, data = null) {
    const sampleData = data || {
      'contact.display_name': 'John Doe',
      'contact.first_name': 'John',
      'contact.last_name': 'Doe',
      'contact.email': 'john.doe@example.com',
      'membership.membership_type': 'Gold Membership',
      'membership.status': 'Current',
      'membership.start_date': '2024-01-01',
      'membership.end_date': '2024-12-31',
      'membership.membership_id': 'M12345',
      'organization.organization_name': 'Example Organization',
      'system.current_date': new Date().toLocaleDateString(),
    };

    const tokenPattern = /\{([^}]+)\}/g;
    let text = textObj.text;
    let match;

    while ((match = tokenPattern.exec(textObj.text)) !== null) {
      const token = match[1];
      const value = sampleData[token] || match[0];
      text = text.replace(match[0], value);
    }

    textObj.set('text', text);
  }

  showPreviewModal(imageData) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Card Preview - ${this.currentSide} Side</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body text-center">
            <img src="${imageData}" class="img-responsive" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 8px;">
            <p class="text-muted mt-3">Preview generated with sample data</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    // Use jQuery modal if available, otherwise basic display
    if (typeof $ !== 'undefined' && $.fn.modal) {
      $(modal).modal('show');
      $(modal).on('hidden.bs.modal', function() {
        document.body.removeChild(modal);
      });
    }
  }

  setCanvasSize(width, height) {
    this.canvas.setWidth(width);
    this.canvas.setHeight(height);
    this.canvas.renderAll();
    this.saveState();
  }

  setBackgroundColor(color) {
    this.canvas.setBackgroundColor(color, this.canvas.renderAll.bind(this.canvas));
    this.cardData[this.currentSide].background_color = color;
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
      this.cardData[this.currentSide].background_image = imageUrl;
      this.saveState();
    });
  }

  exportCard(format = 'png') {
    const dataURL = this.canvas.toDataURL(`image/${format}`);

    // Create download link
    const link = document.createElement('a');
    link.download = `membership-card-${this.currentSide}.${format}`;
    link.href = dataURL;
    link.click();
  }

  createSideToggleToolbar() {

  }
  removeSideToggleToolbar() {

  }
}

function addIcon(iconType) {
  if (!window.cardDesigner) return;

  const icons = {
    'phone': '📞',
    'envelope': '✉️',
    'home': '🏠',
    'globe': '🌍'
  };

  const text = new fabric.Text(icons[iconType] || '⭐', {
    left: 50,
    top: 50,
    fontSize: 24,
    cardSide: window.cardDesigner.currentSide
  });

  window.cardDesigner.canvas.add(text);
  window.cardDesigner.canvas.setActiveObject(text);
  window.cardDesigner.saveState();
}
// Global functions for layer management
function bringToFront() {
  if (window.cardDesigner && window.cardDesigner.selectedElement) {
    window.cardDesigner.canvas.bringToFront(window.cardDesigner.selectedElement);
    window.cardDesigner.canvas.renderAll();
    window.cardDesigner.saveState();
  }
}

function sendToBack() {
  if (window.cardDesigner && window.cardDesigner.selectedElement) {
    window.cardDesigner.canvas.sendToBack(window.cardDesigner.selectedElement);
    window.cardDesigner.canvas.renderAll();
    window.cardDesigner.saveState();
  }
}

function duplicateElement() {
  if (window.cardDesigner && window.cardDesigner.selectedElement) {
    const activeObject = window.cardDesigner.selectedElement;
    activeObject.clone(function(cloned) {
      cloned.set({
        left: cloned.left + 10,
        top: cloned.top + 10,
        cardSide: window.cardDesigner.currentSide
      });
      window.cardDesigner.canvas.add(cloned);
      window.cardDesigner.canvas.setActiveObject(cloned);
      window.cardDesigner.canvas.renderAll();
      window.cardDesigner.saveState();
    });
  }
}

function exportCard(format = 'png') {
  if (window.cardDesigner) {
    window.cardDesigner.exportCard(format);
  }
}

// Global function for downloading both sides
function downloadBothSides(frontData, backData) {
  if (typeof JSZip !== 'undefined') {
    const zip = new JSZip();
    zip.file('membership-card-front.png', frontData.split(',')[1], {base64: true});
    zip.file('membership-card-back.png', backData.split(',')[1], {base64: true});

    zip.generateAsync({type: 'blob'}).then(function(content) {
      const link = document.createElement('a');
      link.href = URL.createObjectURL(content);
      link.download = 'membership-card-both-sides.zip';
      link.click();
    });
  } else {
    // Fallback - download separately
    const link = document.createElement('a');
    link.href = frontData;
    link.download = 'membership-card-front.png';
    link.click();

    setTimeout(() => {
      const link2 = document.createElement('a');
      link2.href = backData;
      link2.download = 'membership-card-back.png';
      link2.click();
    }, 1000);
  }
}

// Initialize designer when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  if (document.getElementById('card-canvas')) {
    // Ensure Fabric.js is loaded
    if (typeof fabric !== 'undefined') {
      window.cardDesigner = new MembershipCardDesigner();
      console.log('Dual-Sided Card Designer initialized successfully');
    } else {
      console.error('Fabric.js not loaded. Please include Fabric.js library.');

      // Try to load Fabric.js dynamically
      const script = document.createElement('script');
      script.src = 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js';
      script.onload = function() {
        window.cardDesigner = new MembershipCardDesigner();
        console.log('Card Designer initialized after loading Fabric.js');
      };
      document.head.appendChild(script);
    }
  }
});

