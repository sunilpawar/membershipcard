{if $action eq 'designer'}
  {* Enhanced Card Designer Interface with Dual-Sided Support *}
  <div class="crm-block crm-form-block">
    <div class="card-designer-container">
      <!-- Toolbar Section -->
      <div class="toolbar-section">
        <div class="template-info">
          <h3>{if $template.id}{ts}Edit Template: {$template.name}{/ts}{else}{ts}Create New Template{/ts}{/if}</h3>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <input type="hidden" id="template-id" value="{$template.id|default:''}">
                <label for="template-name">{ts}Template Name{/ts}:</label>
                <input type="text" id="template-name" class="form-control"
                       placeholder="{ts}Enter template name{/ts}"
                       value="{$template.name|default:''}" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="template-description">{ts}Description{/ts}:</label>
                <input type="text" id="template-description" class="form-control"
                       placeholder="{ts}Template description{/ts}"
                       value="{$template.description|default:''}">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <div class="dual-sided-toggle">
                  <label for="is-dual-sided">
                    <input type="checkbox" id="is-dual-sided"
                           {if $template.is_dual_sided}checked{/if}>
                    <i class="fa fa-clone"></i>
                    {ts}Enable Back Side{/ts}
                  </label>
                  <small class="form-text text-muted">
                    {ts}Create a two-sided membership card{/ts}
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card-settings">
          <div class="form-group">
            <label for="card-width">{ts}Width{/ts}:</label>
            <input type="number" id="card-width" class="form-control"
                   value="{$template.card_width|default:350}" min="200" max="800">
          </div>
          <div class="form-group">
            <label for="card-height">{ts}Height{/ts}:</label>
            <input type="number" id="card-height" class="form-control"
                   value="{$template.card_height|default:220}" min="100" max="500">
          </div>

          <!-- Front Side Settings -->
          <div class="side-specific-fields front-side" id="front-side-settings">
            <h4><i class="fa fa-credit-card"></i> {ts}Front Side Settings{/ts}</h4>
            <div class="form-group">
              <label for="front-bg-color">{ts}Background{/ts}:</label>
              <input type="color" id="front-bg-color" class="form-control"
                     value="{if $template.front_background_color}{$template.front_background_color}{else}#ffffff{/if}">
            </div>
            <div class="form-group">
              <label for="front-bg-image">{ts}Background Image{/ts}:</label>
              <input type="file" id="front-bg-image" class="form-control-file" accept="image/*">
              {if !empty($template.front_background_image)}
                <small class="form-text text-muted">
                  {ts}Current:{/ts} {$template.front_background_image|truncate:30}
                </small>
              {/if}
            </div>
          </div>

          <!-- Back Side Settings -->
          <div class="side-specific-fields back-side" id="back-side-settings" style="display: none;">
            <h4><i class="fa fa-credit-card fa-flip-horizontal"></i> {ts}Back Side Settings{/ts}</h4>
            <div class="form-group">
              <label for="back-bg-color">{ts}Background{/ts}:</label>
              <input type="color" id="back-bg-color" class="form-control"
                     value="{if $template.back_background_color}{$template.back_background_color}{else}#ffffff{/if}">
            </div>
            <div class="form-group">
              <label for="back-bg-image">{ts}Background Image{/ts}:</label>
              <input type="file" id="back-bg-image" class="form-control-file" accept="image/*">
              {if $template.back_background_image}
                <small class="form-text text-muted">
                  {ts}Current:{/ts} {$template.back_background_image|truncate:30}
                </small>
              {/if}
            </div>
          </div>
        </div>
      </div>
      <div class="card-toolbar" id="card-toolbar">
        <!-- Side toggle will be added dynamically by JavaScript -->
        <!-- Additional toolbar buttons will be added here -->
      </div>
      <!-- Main Designer Area -->
      <div class="main-designer">
        <!-- Enhanced Token Panel with Side Recommendations -->
        <div class="token-panel" id="token-panel">
          <h3>{ts}Available Tokens{/ts}</h3>
          <p class="text-muted small">{ts}Drag tokens to the card or click to insert{/ts}</p>

          <!-- Side-specific recommendations will be added by JavaScript -->

          {foreach from=$tokens key=category item=tokenGroup}
            <div class="token-category">
              <h4>{$category|upper|replace:'_':' '}</h4>
              <div class="token-list">
                {foreach from=$tokenGroup key=tokenKey item=tokenLabel}
                  <div class="token-item" draggable="true" data-token="{literal}{{/literal}{$category}.{$tokenKey}{literal}}{/literal}"
                       data-token-type="text" title="{$tokenLabel}">
                    {$tokenLabel}
                  </div>
                {/foreach}
              </div>
            </div>
          {/foreach}

          <!-- Quick Actions -->
          <div class="token-quick-actions">
            <h4>{ts}Quick Actions{/ts}</h4>
            <div class="quick-action-buttons">
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addMemberPhoto()">
                <i class="fa fa-user"></i> {ts}Member Photo{/ts}
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOrganizationLogo()">
                <i class="fa fa-building"></i> {ts}Org Logo{/ts}
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addSignatureLine()">
                <i class="fa fa-pencil"></i> {ts}Signature{/ts}
              </button>
            </div>
          </div>
        </div>

        <!-- Enhanced Canvas Section -->
        <div class="canvas-section">
          <div class="canvas-container">
            <!-- Canvas Side Indicator -->
            <div class="canvas-side-indicator" id="canvas-side-indicator">
              <i class="fa fa-credit-card"></i> <span id="canvas-side-text">{ts}Front Side{/ts}</span>
            </div>

            <div class="canvas-wrapper">
              <canvas id="card-canvas"
                      width="{$template.card_width|default:350}"
                      height="{$template.card_height|default:220}"></canvas>
            </div>

            <!-- Canvas Guidelines -->
            <div class="canvas-guidelines">
              <div class="guideline-ruler horizontal" id="horizontal-ruler"></div>
              <div class="guideline-ruler vertical" id="vertical-ruler"></div>
            </div>
          </div>

          <!-- Template Actions -->
          <div class="template-actions">
            <div class="action-group primary-actions">
              <button type="button" class="btn btn-secondary" id="preview-card">
                <i class="fa fa-eye"></i> {ts}Preview Current Side{/ts}
              </button>
              <button type="button" class="btn btn-success" id="preview-both-sides">
                <i class="fa fa-clone"></i> {ts}Preview Both Sides{/ts}
              </button>
              <button type="button" class="btn btn-info" onclick="exportCard('png')">
                <i class="fa fa-download"></i> {ts}Export PNG{/ts}
              </button>
              <button type="button" class="btn btn-warning" onclick="exportCard('pdf')">
                <i class="fa fa-file-pdf-o"></i> {ts}Export PDF{/ts}
              </button>
            </div>

            <div class="action-group secondary-actions">
              <button type="button" class="btn btn-primary" id="save-template">
                <i class="fa fa-save"></i> {ts}Save Template{/ts}
              </button>
              <a href="{crmURL p='civicrm/membership-card-templates'}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> {ts}Back to Templates{/ts}
              </a>
            </div>
          </div>
        </div>

        <!-- Enhanced Property Panel -->
        <div class="property-panel" id="property-panel">
          <h4><i class="fa fa-cogs"></i> {ts}Properties{/ts}</h4>
          <p class="text-muted small">{ts}Select an element to edit its properties{/ts}</p>

          <!-- Side Information Panel -->
          <div class="side-info-panel">
            <div class="current-side-info">
              <h5 id="current-side-title">
                <i class="fa fa-credit-card" id="side-icon"></i>
                <span id="side-name">{ts}Front Side{/ts}</span>
              </h5>
              <div class="side-stats">
                <small class="text-muted">
                  <span id="element-count">0</span> {ts}elements{/ts} |
                  <span id="canvas-dimensions">350×220px</span>
                </small>
              </div>
            </div>
          </div>

          <!-- Text Properties -->
          <div id="text-properties" class="property-section" style="display: none;">
            <h5><i class="fa fa-font"></i> {ts}Text Properties{/ts}</h5>
            <div class="form-group">
              <label for="text-content">{ts}Text{/ts}:</label>
              <textarea id="text-content" class="form-control" rows="3"
                        placeholder="{ts}Enter text or drag tokens here{/ts}"></textarea>
              <div class="text-tools">
                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertCommonText('name')">
                  <i class="fa fa-user"></i> {ts}Name{/ts}
                </button>
                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertCommonText('id')">
                  <i class="fa fa-hashtag"></i> {ts}ID{/ts}
                </button>
                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertCommonText('expires')">
                  <i class="fa fa-calendar"></i> {ts}Expires{/ts}
                </button>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="font-size">{ts}Font Size{/ts}:</label>
                  <input type="number" id="font-size" class="form-control" min="8" max="72" value="16">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="text-color">{ts}Color{/ts}:</label>
                  <input type="color" id="text-color" class="form-control" value="#000000">
                </div>
              </div>
            </div>
            <div class="form-group">
              <label for="font-family">{ts}Font Family{/ts}:</label>
              <select id="font-family" class="form-control">
                <option value="Arial">Arial</option>
                <option value="Helvetica">Helvetica</option>
                <option value="Times New Roman">Times New Roman</option>
                <option value="Courier New">Courier New</option>
                <option value="Georgia">Georgia</option>
                <option value="Verdana">Verdana</option>
                <option value="Impact">Impact</option>
              </select>
            </div>
            <div class="form-group">
              <label for="text-align">{ts}Alignment{/ts}:</label>
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary" onclick="setTextAlign('left')">
                  <i class="fa fa-align-left"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="setTextAlign('center')">
                  <i class="fa fa-align-center"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="setTextAlign('right')">
                  <i class="fa fa-align-right"></i>
                </button>
              </div>
            </div>
            <div class="form-group">
              <div class="text-style-options">
                <label class="checkbox-inline">
                  <input type="checkbox" id="text-bold"> <strong>{ts}Bold{/ts}</strong>
                </label>
                <label class="checkbox-inline">
                  <input type="checkbox" id="text-italic"> <em>{ts}Italic{/ts}</em>
                </label>
                <label class="checkbox-inline">
                  <input type="checkbox" id="text-underline"> <u>{ts}Underline{/ts}</u>
                </label>
              </div>
            </div>
          </div>

          <!-- Image Properties -->
          <div id="image-properties" class="property-section" style="display: none;">
            <h5><i class="fa fa-image"></i> {ts}Image Properties{/ts}</h5>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="image-width">{ts}Width{/ts}:</label>
                  <input type="number" id="image-width" class="form-control" min="10" max="500">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="image-height">{ts}Height{/ts}:</label>
                  <input type="number" id="image-height" class="form-control" min="10" max="500">
                </div>
              </div>
            </div>
            <div class="form-group">
              <label for="image-radius">{ts}Border Radius{/ts}:</label>
              <input type="range" id="image-radius" class="form-control" min="0" max="50" value="0">
              <small class="form-text text-muted">{ts}0 = Square, 50 = Circular{/ts}</small>
            </div>
            <div class="form-group">
              <label for="image-opacity">{ts}Opacity{/ts}:</label>
              <input type="range" id="image-opacity" class="form-control" min="0" max="1" step="0.1" value="1">
            </div>
            <div class="image-style-options">
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="fitImageToCard()">
                <i class="fa fa-expand"></i> {ts}Fit to Card{/ts}
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropImageSquare()">
                <i class="fa fa-crop"></i> {ts}Make Square{/ts}
              </button>
            </div>
          </div>

          <!-- Position Properties -->
          <div id="position-properties" class="property-section" style="display: none;">
            <h5><i class="fa fa-arrows"></i> {ts}Position & Transform{/ts}</h5>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="pos-x">{ts}X Position{/ts}:</label>
                  <input type="number" id="pos-x" class="form-control">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="pos-y">{ts}Y Position{/ts}:</label>
                  <input type="number" id="pos-y" class="form-control">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="rotation">{ts}Rotation{/ts}:</label>
                  <input type="number" id="rotation" class="form-control" min="-180" max="180" value="0">
                  <small class="form-text text-muted">{ts}Degrees{/ts}</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="element-opacity">{ts}Opacity{/ts}:</label>
                  <input type="range" id="element-opacity" class="form-control" min="0" max="1" step="0.1" value="1">
                </div>
              </div>
            </div>
            <div class="position-presets">
              <h6>{ts}Quick Position{/ts}:</h6>
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary" onclick="alignElement('top-left')">
                  <i class="fa fa-arrow-up"></i><i class="fa fa-arrow-left"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="alignElement('top-center')">
                  <i class="fa fa-arrow-up"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="alignElement('top-right')">
                  <i class="fa fa-arrow-up"></i><i class="fa fa-arrow-right"></i>
                </button>
              </div>
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary" onclick="alignElement('center-left')">
                  <i class="fa fa-arrow-left"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="alignElement('center')">
                  <i class="fa fa-dot-circle-o"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="alignElement('center-right')">
                  <i class="fa fa-arrow-right"></i>
                </button>
              </div>
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary" onclick="alignElement('bottom-left')">
                  <i class="fa fa-arrow-down"></i><i class="fa fa-arrow-left"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="alignElement('bottom-center')">
                  <i class="fa fa-arrow-down"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="alignElement('bottom-right')">
                  <i class="fa fa-arrow-down"></i><i class="fa fa-arrow-right"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Layer Management -->
          <div id="layer-properties" class="property-section">
            <h5><i class="fa fa-layer-group"></i> {ts}Layers & Actions{/ts}</h5>
            <div class="layer-actions">
              <div class="btn-group-vertical w-100" role="group">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bringToFront()">
                  <i class="fa fa-arrow-up"></i> {ts}Bring to Front{/ts}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="sendToBack()">
                  <i class="fa fa-arrow-down"></i> {ts}Send to Back{/ts}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="duplicateElement()">
                  <i class="fa fa-copy"></i> {ts}Duplicate{/ts}
                </button>
                <button type="button" class="btn btn-sm btn-outline-info" onclick="copyToOtherSide()" id="copy-to-other-side-btn">
                  <i class="fa fa-clone"></i> <span id="copy-to-side-text">{ts}Copy to Back{/ts}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Element Library -->
          <div class="element-library">
            <h5><i class="fa fa-th"></i> {ts}Element Library{/ts}</h5>
            <div class="library-categories">
              <div class="library-category">
                <h6>{ts}Shapes{/ts}</h6>
                <div class="element-grid">
                  <button type="button" class="element-btn" onclick="addShape('rectangle')">
                    <i class="fa fa-square-o"></i>
                  </button>
                  <button type="button" class="element-btn" onclick="addShape('circle')">
                    <i class="fa fa-circle-o"></i>
                  </button>
                  <button type="button" class="element-btn" onclick="addShape('line')">
                    <i class="fa fa-minus"></i>
                  </button>
                </div>
              </div>
              <div class="library-category">
                <h6>{ts}Icons{/ts}</h6>
                <div class="element-grid">
                  <button type="button" class="element-btn" onclick="addIcon('phone')">
                    <i class="fa fa-phone"></i>
                  </button>
                  <button type="button" class="element-btn" onclick="addIcon('envelope')">
                    <i class="fa fa-envelope"></i>
                  </button>
                  <button type="button" class="element-btn" onclick="addIcon('home')">
                    <i class="fa fa-home"></i>
                  </button>
                  <button type="button" class="element-btn" onclick="addIcon('globe')">
                    <i class="fa fa-globe"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

{* Include JavaScript and CSS *}
{literal}
  <script>
    // Pass template data to JavaScript
    window.templateData = {/literal}{$template|@json_encode}{literal};
    window.membershipTokens = {/literal}{$tokens|@json_encode}{literal};

    // Enhanced functions for dual-sided support
    function addMemberPhoto() {
      if (window.cardDesigner) {
        window.cardDesigner.insertToken('{contact.image_URL}');
      }
    }

    function addOrganizationLogo() {
      if (window.cardDesigner) {
        window.cardDesigner.insertToken('{organization.organization_logo}');
      }
    }

    function addSignatureLine() {
      if (window.cardDesigner) {
        window.cardDesigner.addText();
        setTimeout(() => {
          if (window.cardDesigner.selectedElement) {
            window.cardDesigner.selectedElement.set('text', '_________________');
            window.cardDesigner.canvas.renderAll();
          }
        }, 100);
      }
    }

    function insertCommonText(type) {
      const tokens = {
        'name': '{contact.display_name}',
        'id': '{membership.membership_id}',
        'expires': 'Expires: {membership.end_date}'
      };

      if (window.cardDesigner && tokens[type]) {
        window.cardDesigner.insertToken(tokens[type]);
      }
    }

    function setTextAlign(alignment) {
      if (window.cardDesigner && window.cardDesigner.selectedElement && window.cardDesigner.selectedElement.type === 'text') {
        window.cardDesigner.selectedElement.set('textAlign', alignment);
        window.cardDesigner.canvas.renderAll();
        window.cardDesigner.saveState();
      }
    }

    function fitImageToCard() {
      if (window.cardDesigner && window.cardDesigner.selectedElement && window.cardDesigner.selectedElement.type === 'image') {
        const img = window.cardDesigner.selectedElement;
        const canvas = window.cardDesigner.canvas;
        img.set({
          scaleX: canvas.width / img.width,
          scaleY: canvas.height / img.height,
          left: 0,
          top: 0
        });
        window.cardDesigner.canvas.renderAll();
        window.cardDesigner.saveState();
      }
    }

    function cropImageSquare() {
      if (window.cardDesigner && window.cardDesigner.selectedElement && window.cardDesigner.selectedElement.type === 'image') {
        const img = window.cardDesigner.selectedElement;
        const size = Math.min(img.width, img.height);
        img.set({
          scaleX: size / img.width,
          scaleY: size / img.height
        });
        window.cardDesigner.canvas.renderAll();
        window.cardDesigner.saveState();
      }
    }

    function alignElement(position) {
      if (!window.cardDesigner || !window.cardDesigner.selectedElement) return;

      const element = window.cardDesigner.selectedElement;
      const canvas = window.cardDesigner.canvas;
      const positions = {
        'top-left': { left: 10, top: 10 },
        'top-center': { left: canvas.width / 2, top: 10 },
        'top-right': { left: canvas.width - 10, top: 10 },
        'center-left': { left: 10, top: canvas.height / 2 },
        'center': { left: canvas.width / 2, top: canvas.height / 2 },
        'center-right': { left: canvas.width - 10, top: canvas.height / 2 },
        'bottom-left': { left: 10, top: canvas.height - 10 },
        'bottom-center': { left: canvas.width / 2, top: canvas.height - 10 },
        'bottom-right': { left: canvas.width - 10, top: canvas.height - 10 }
      };

      if (positions[position]) {
        element.set(positions[position]);
        canvas.renderAll();
        window.cardDesigner.saveState();
      }
    }

    function addShape(shape) {
      if (!window.cardDesigner) return;

      switch (shape) {
        case 'rectangle':
          const rect = new fabric.Rect({
            left: 50,
            top: 50,
            width: 100,
            height: 60,
            fill: 'transparent',
            stroke: '#000000',
            strokeWidth: 2,
            cardSide: window.cardDesigner.currentSide
          });
          window.cardDesigner.canvas.add(rect);
          window.cardDesigner.canvas.setActiveObject(rect);
          break;

        case 'circle':
          const circle = new fabric.Circle({
            left: 50,
            top: 50,
            radius: 30,
            fill: 'transparent',
            stroke: '#000000',
            strokeWidth: 2,
            cardSide: window.cardDesigner.currentSide
          });
          window.cardDesigner.canvas.add(circle);
          window.cardDesigner.canvas.setActiveObject(circle);
          break;

        case 'line':
          const line = new fabric.Line([0, 0, 100, 0], {
            left: 50,
            top: 50,
            stroke: '#000000',
            strokeWidth: 2,
            cardSide: window.cardDesigner.currentSide
          });
          window.cardDesigner.canvas.add(line);
          window.cardDesigner.canvas.setActiveObject(line);
          break;
      }
      window.cardDesigner.canvas.renderAll();
      window.cardDesigner.saveState();
    }

    function copyToOtherSide() {
      if (!window.cardDesigner || !window.cardDesigner.selectedElement || !window.cardDesigner.isDualSided) {
        return;
      }

      const activeObject = window.cardDesigner.selectedElement;
      const targetSide = window.cardDesigner.currentSide === 'front' ? 'back' : 'front';

      activeObject.clone(function(cloned) {
        cloned.set({
          cardSide: targetSide
        });

        // Store the cloned element for the other side
        if (!window.cardDesigner.cardData[targetSide]) {
          window.cardDesigner.cardData[targetSide] = { elements: [] };
        }

        // Add to the target side's elements
        window.cardDesigner.cardData[targetSide].elements.push(cloned.toObject());

        // Show success message
        CRM.alert('{/literal}{ts escape="js"}Element copied to {/ts}{literal}' + targetSide + '{/literal}{ts escape="js"} side{/ts}{literal}', '{/literal}{ts escape="js"}Success{/ts}{literal}', 'success');
      });
    }

    function exportCard(format) {
      if (!window.cardDesigner) return;

      if (format === 'png') {
        // Export current side as PNG
        const dataURL = window.cardDesigner.canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = 'membership-card-' + window.cardDesigner.currentSide + '.png';
        link.href = dataURL;
        link.click();
      } else if (format === 'pdf') {
        // Export as PDF (requires jsPDF integration)
        if (typeof jsPDF !== 'undefined') {
          const pdf = new jsPDF();
          const imgData = window.cardDesigner.canvas.toDataURL('image/png');
          pdf.addImage(imgData, 'PNG', 10, 10, 190, 120);

          // If dual-sided, add back side
          if (window.cardDesigner.isDualSided && window.cardDesigner.cardData.back) {
            pdf.addPage();
            // Switch to back side temporarily for export
            const currentSide = window.cardDesigner.currentSide;
            window.cardDesigner.switchSide('back');
            const backImgData = window.cardDesigner.canvas.toDataURL('image/png');
            pdf.addImage(backImgData, 'PNG', 10, 10, 190, 120);
            window.cardDesigner.switchSide(currentSide);
          }

          pdf.save('membership-card.pdf');
        } else {
          CRM.alert('{/literal}{ts escape="js"}PDF export requires jsPDF library{/ts}{literal}', '{/literal}{ts escape="js"}Error{/ts}{literal}', 'error');
        }
      }
    }

    // Initialize dual-sided toggle
    document.addEventListener('DOMContentLoaded', function() {
      const dualSidedToggle = document.getElementById('is-dual-sided');
      const backSideSettings = document.getElementById('back-side-settings');

      function toggleBackSideSettings() {
        if (dualSidedToggle.checked) {
          backSideSettings.style.display = 'block';
          if (window.cardDesigner) {
            window.cardDesigner.isDualSided = true;
            window.cardDesigner.createSideToggleToolbar();
          }
        } else {
          backSideSettings.style.display = 'none';
          if (window.cardDesigner) {
            window.cardDesigner.isDualSided = false;
            window.cardDesigner.removeSideToggleToolbar();
          }
        }
      }

      dualSidedToggle.addEventListener('change', toggleBackSideSettings);

      // Initialize on page load
      if (dualSidedToggle.checked) {
        toggleBackSideSettings();
      }

      // Initialize save template functionality
      const saveButton = document.getElementById('save-template');
      if (saveButton) {
        saveButton.addEventListener('click', function() {
          if (window.cardDesigner) {
            window.cardDesigner.saveTemplate();
          }
        });
      }

      // Initialize preview functionality
      const previewButton = document.getElementById('preview-card');
      const previewBothButton = document.getElementById('preview-both-sides');

      if (previewButton) {
        previewButton.addEventListener('click', function() {
          if (window.cardDesigner) {
            window.cardDesigner.previewCard();
          }
        });
      }

      if (previewBothButton) {
        previewBothButton.addEventListener('click', function() {
          if (window.cardDesigner) {
            window.cardDesigner.previewBothSides();
          }
        });
      }

      // Initialize card dimension change handlers
      const widthInput = document.getElementById('card-width');
      const heightInput = document.getElementById('card-height');

      if (widthInput) {
        widthInput.addEventListener('change', function() {
          if (window.cardDesigner) {
            window.cardDesigner.updateCardDimensions(this.value, null);
          }
        });
      }

      if (heightInput) {
        heightInput.addEventListener('change', function() {
          if (window.cardDesigner) {
            window.cardDesigner.updateCardDimensions(null, this.value);
          }
        });
      }

      // Initialize background color handlers
      const frontBgColor = document.getElementById('front-bg-color');
      const backBgColor = document.getElementById('back-bg-color');

      if (frontBgColor) {
        frontBgColor.addEventListener('change', function() {
          if (window.cardDesigner) {
            window.cardDesigner.updateBackgroundColor('front', this.value);
          }
        });
      }

      if (backBgColor) {
        backBgColor.addEventListener('change', function() {
          if (window.cardDesigner) {
            window.cardDesigner.updateBackgroundColor('back', this.value);
          }
        });
      }

      // Initialize background image handlers
      const frontBgImage = document.getElementById('front-bg-image');
      const backBgImage = document.getElementById('back-bg-image');

      if (frontBgImage) {
        frontBgImage.addEventListener('change', function() {
          if (window.cardDesigner && this.files[0]) {
            window.cardDesigner.updateBackgroundImage('front', this.files[0]);
          }
        });
      }

      if (backBgImage) {
        backBgImage.addEventListener('change', function() {
          if (window.cardDesigner && this.files[0]) {
            window.cardDesigner.updateBackgroundImage('back', this.files[0]);
          }
        });
      }
    });

  </script>
{/literal}
{else}
{* List View for Templates *}
  <div class="crm-block crm-content-block">
    <h1>{ts}Membership Card Templates{/ts}</h1>

    <div class="crm-submit-buttons">
      <a href="{crmURL p='civicrm/membership-card-templates' q='action=add&reset=1'}" class="btn btn-primary">
        <i class="fa fa-plus"></i> {ts}Create New Template{/ts}
      </a>
    </div>

    {if $templates}
      <div class="template-grid">
        {foreach from=$templates item=template}
          <div class="template-card">
            <div class="template-preview">
              {if !empty($template.front_background_image)}
                <div class="template-placeholder" style="background-color: {$template.front_background_color|default:'#ffffff'}">
                  <i class="fa fa-credit-card fa-3x"></i>
                  <img src="{$template.front_background_image}" alt="{$template.name}" class="template-thumbnail">
                </div>
              {else}
                <div class="template-placeholder" style="background-color: {$template.front_background_color|default:'#ffffff'}">
                  <i class="fa fa-credit-card fa-3x"></i>
                </div>
              {/if}
              {if !empty($template.is_dual_sided)}
                <div class="dual-sided-badge">
                  <i class="fa fa-clone"></i> {ts}Dual-Sided{/ts}
                </div>
              {/if}
            </div>

            <div class="template-info">
              <h4>{$template.name}</h4>
              <p class="text-muted">{$template.description}</p>
              <div class="template-meta">
                <small class="text-muted">
                  {$template.card_width}×{$template.card_height}px
                  {if $template.modified_date}
                    | {ts}Updated{/ts}: {$template.modified_date|date_format}
                  {/if}
                </small>
              </div>
            </div>

            <div class="template-actions">
              <a href="{crmURL p='civicrm/membership-card-templates' q="action=update&id=`$template.id`"}"
                 class="btn btn-sm btn-primary">
                <i class="fa fa-edit"></i> {ts}Edit{/ts}
              </a>
              <a href="{crmURL p='civicrm/membership-card-templates' q="action=preview&id=`$template.id`"}"
                 class="btn btn-sm btn-secondary">
                <i class="fa fa-eye"></i> {ts}Preview{/ts}
              </a>
              <a href="{crmURL p='civicrm/membership-card-templates' q="action=duplicate&id=`$template.id`"}"
                 class="btn btn-sm btn-info">
                <i class="fa fa-copy"></i> {ts}Duplicate{/ts}
              </a>
              <a href="{crmURL p='civicrm/membership-card-templates' q="action=delete&id=`$template.id`"}"
                 class="btn btn-sm btn-danger"
                 onclick="return confirm('{ts escape="js"}Are you sure you want to delete this template?{/ts}')">
                <i class="fa fa-trash"></i> {ts}Delete{/ts}
              </a>
            </div>
          </div>
        {/foreach}
      </div>
    {else}
      <div class="empty-state">
        <div class="empty-state-content">
          <i class="fa fa-credit-card fa-4x text-muted"></i>
          <h3>{ts}No Templates Found{/ts}</h3>
          <p class="text-muted">{ts}Get started by creating your first membership card template.{/ts}</p>
          <a href="{crmURL p='civicrm/membership-card-templates' q='action=add'}" class="btn btn-primary btn-lg">
            <i class="fa fa-plus"></i> {ts}Create Your First Template{/ts}
          </a>
        </div>
      </div>
    {/if}
  </div>

{literal}
  <style>
    .template-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .template-card {
      background: white;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      overflow: hidden;
      transition: all 0.2s;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .template-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .template-preview {
      position: relative;
      height: 200px;
      background: #f8f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .template-thumbnail {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .template-placeholder {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #6c757d;
    }

    .dual-sided-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background: #007bff;
      color: white;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 500;
    }

    .template-info {
      padding: 15px;
    }

    .template-info h4 {
      margin-bottom: 8px;
      color: #2c3e50;
    }

    .template-meta {
      margin-top: 10px;
    }

    .template-actions {
      padding: 0 15px 15px;
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
    }

    .empty-state-content {
      max-width: 400px;
      margin: 0 auto;
    }

    .empty-state-content h3 {
      margin: 20px 0 10px;
      color: #495057;
    }

    .empty-state-content .btn-lg {
      margin-top: 20px;
    }

    @media (max-width: 768px) {
      .template-grid {
        grid-template-columns: 1fr;
      }

      .template-actions {
        justify-content: center;
      }
    }
  </style>
{/literal}

{/if}
