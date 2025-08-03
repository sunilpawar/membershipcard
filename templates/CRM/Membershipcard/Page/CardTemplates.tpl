{if $action eq 'designer'}
  {* Card Designer Interface *}
  <div class="crm-block crm-form-block">
    <div class="card-designer-container">
      <!-- Toolbar Section -->
      <div class="toolbar-section">
        <div class="template-info">
          <h3>{if $template.id}{ts}Edit Template: {$template.name}{/ts}{else}{ts}Create New Template{/ts}{/if}</h3>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="template-name">{ts}Template Name{/ts}:</label>
                <input type="text" id="template-name" class="form-control"
                       placeholder="{ts}Enter template name{/ts}"
                       value="{$template.name|default:''}" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="template-description">{ts}Description{/ts}:</label>
                <input type="text" id="template-description" class="form-control"
                       placeholder="{ts}Template description{/ts}"
                       value="{$template.description|default:''}">
              </div>
            </div>
          </div>
        </div>

        <div class="card-toolbar" id="card-toolbar">
          <!-- Buttons will be added dynamically by JavaScript -->
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
          <div class="form-group">
            <label for="bg-color">{ts}Background{/ts}:</label>
            <input type="color" id="bg-color" class="form-control"
                   value="{$template.background_color|default:'#ffffff'}">
          </div>
          <div class="form-group">
            <label for="bg-image">{ts}Background Image{/ts}:</label>
            <input type="file" id="bg-image" class="form-control-file" accept="image/*">
          </div>
        </div>
      </div>

      <!-- Main Designer Area -->
      <div class="main-designer">
        <!-- Token Panel -->
        <div class="token-panel" id="token-panel">
          <h3>{ts}Available Tokens{/ts}</h3>
          <p class="text-muted small">{ts}Drag tokens to the card or click to insert{/ts}</p>

          {foreach from=$tokens key=category item=tokenGroup}
            <div class="token-category">
              <h4>{$category|upper|replace:'_':' '}</h4>
              <div class="token-list">
                {foreach from=$tokenGroup key=tokenKey item=tokenLabel}
                  <div class="token-item" draggable="true" data-token="{literal}{{/literal}{$category}.{$tokenKey}{literal}}{/literal}">
                    {$tokenLabel}
                  </div>
                {/foreach}
              </div>
            </div>
          {/foreach}
        </div>

        <!-- Canvas Section -->
        <div class="canvas-section">
          <div class="canvas-container">
            <div class="canvas-wrapper">
              <canvas id="card-canvas" width="{$template.card_width|default:350}" height="{$template.card_height|default:220}"></canvas>
            </div>
          </div>

          <div class="template-actions">
            <button type="button" class="btn btn-secondary" id="preview-card">
              <i class="fa fa-eye"></i> {ts}Preview{/ts}
            </button>
            <button type="button" class="btn btn-info" onclick="exportCard('png')">
              <i class="fa fa-download"></i> {ts}Export PNG{/ts}
            </button>
            <button type="button" class="btn btn-success" id="save-template">
              <i class="fa fa-save"></i> {ts}Save Template{/ts}
            </button>
            <a href="{crmURL p='civicrm/membership-card-templates'}" class="btn btn-secondary">
              <i class="fa fa-arrow-left"></i> {ts}Back to Templates{/ts}
            </a>
          </div>
        </div>

        <!-- Property Panel -->
        <div class="property-panel" id="property-panel">
          <h3>{ts}Properties{/ts}</h3>
          <p class="text-muted small">{ts}Select an element to edit its properties{/ts}</p>

          <!-- Text Properties -->
          <div id="text-properties" style="display: none;">
            <h4>{ts}Text Properties{/ts}</h4>
            <div class="form-group">
              <label for="text-content">{ts}Text{/ts}:</label>
              <textarea id="text-content" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label for="font-size">{ts}Font Size{/ts}:</label>
              <input type="number" id="font-size" class="form-control" min="8" max="72" value="16">
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
              </select>
            </div>
            <div class="form-group">
              <label for="text-color">{ts}Color{/ts}:</label>
              <input type="color" id="text-color" class="form-control" value="#000000">
            </div>
            <div class="form-group">
              <label for="text-align">{ts}Alignment{/ts}:</label>
              <select id="text-align" class="form-control">
                <option value="left">{ts}Left{/ts}</option>
                <option value="center">{ts}Center{/ts}</option>
                <option value="right">{ts}Right{/ts}</option>
              </select>
            </div>
            <div class="form-group">
              <label>
                <input type="checkbox" id="text-bold"> {ts}Bold{/ts}
              </label>
            </div>
            <div class="form-group">
              <label>
                <input type="checkbox" id="text-italic"> {ts}Italic{/ts}
              </label>
            </div>
          </div>

          <!-- Image Properties -->
          <div id="image-properties" style="display: none;">
            <h4>{ts}Image Properties{/ts}</h4>
            <div class="form-group">
              <label for="image-width">{ts}Width{/ts}:</label>
              <input type="number" id="image-width" class="form-control" min="10" max="500">
            </div>
            <div class="form-group">
              <label for="image-height">{ts}Height{/ts}:</label>
              <input type="number" id="image-height" class="form-control" min="10" max="500">
            </div>
            <div class="form-group">
              <label for="image-radius">{ts}Border Radius{/ts}:</label>
              <input type="number" id="image-radius" class="form-control" min="0" max="50" value="0">
            </div>
            <div class="form-group">
              <label for="image-opacity">{ts}Opacity{/ts}:</label>
              <input type="range" id="image-opacity" class="form-control" min="0" max="1" step="0.1" value="1">
            </div>
          </div>

          <!-- Position Properties -->
          <div id="position-properties" style="display: none;">
            <h4>{ts}Position & Transform{/ts}</h4>
            <div class="form-group">
              <label for="pos-x">{ts}X Position{/ts}:</label>
              <input type="number" id="pos-x" class="form-control">
            </div>
            <div class="form-group">
              <label for="pos-y">{ts}Y Position{/ts}:</label>
              <input type="number" id="pos-y" class="form-control">
            </div>
            <div class="form-group">
              <label for="rotation">{ts}Rotation (degrees){/ts}:</label>
              <input type="number" id="rotation" class="form-control" min="-180" max="180" value="0">
            </div>
            <div class="form-group">
              <label for="scale-x">{ts}Scale X{/ts}:</label>
              <input type="number" id="scale-x" class="form-control" min="0.1" max="3" step="0.1" value="1">
            </div>
            <div class="form-group">
              <label for="scale-y">{ts}Scale Y{/ts}:</label>
              <input type="number" id="scale-y" class="form-control" min="0.1" max="3" step="0.1" value="1">
            </div>
          </div>

          <!-- Layer Management -->
          <div id="layer-properties">
            <h4>{ts}Layers{/ts}</h4>
            <div class="btn-group-vertical w-100">
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bringToFront()">
                <i class="fa fa-arrow-up"></i> {ts}Bring to Front{/ts}
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="sendToBack()">
                <i class="fa fa-arrow-down"></i> {ts}Send to Back{/ts}
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="duplicateElement()">
                <i class="fa fa-copy"></i> {ts}Duplicate{/ts}
              </button>
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
  </script>
{/literal}

{else}
  {* Template List View *}
  <div class="crm-block crm-content-block">
    <div class="crm-submit-buttons">
      <a href="{crmURL p='civicrm/membership-card-templates' q='action=add'}" class="btn btn-primary">
        <i class="fa fa-plus"></i> {ts}Add New Template{/ts}
      </a>
    </div>

    {if $templates}
      <div class="crm-results-block">
        <table class="selector row-highlight">
          <thead>
          <tr>
            <th>{ts}Name{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Status{/ts}</th>
            <th>{ts}Created Date{/ts}</th>
            <th>{ts}Actions{/ts}</th>
          </tr>
          </thead>
          <tbody>
          {foreach from=$templates item=template}
            <tr id="template-{$template.id}" class="{cycle values="odd-row,even-row"}">
              <td class="crm-template-name">
                <strong>{$template.name}</strong>
              </td>
              <td class="crm-template-description">
                {$template.description|truncate:50}
              </td>
              <td class="crm-template-status">
                {if $template.is_active}
                  <span class="label label-success">{ts}Active{/ts}</span>
                {else}
                  <span class="label label-default">{ts}Inactive{/ts}</span>
                {/if}
              </td>
              <td class="crm-template-created">
                {$template.created_date|crmDate}
              </td>
              <td class="crm-template-actions">
                  <span class="btn-group">
                    <a href="{crmURL p='civicrm/membership-card-templates' q="action=update&id=`$template.id`"}"
                       class="btn btn-sm btn-primary" title="{ts}Edit{/ts}">
                      <i class="fa fa-pencil"></i>
                    </a>
                    <a href="{crmURL p='civicrm/membership-card-templates' q="action=preview&id=`$template.id`"}"
                       class="btn btn-sm btn-info" title="{ts}Preview{/ts}">
                      <i class="fa fa-eye"></i>
                    </a>
                    <a href="{crmURL p='civicrm/membership-card-templates' q="action=copy&id=`$template.id`"}"
                       class="btn btn-sm btn-secondary" title="{ts}Copy{/ts}">
                      <i class="fa fa-copy"></i>
                    </a>
                    <a href="{crmURL p='civicrm/membership-card-templates' q="action=delete&id=`$template.id`"}"
                       class="btn btn-sm btn-danger" title="{ts}Delete{/ts}"
                       onclick="return confirm('{ts escape="js"}Are you sure you want to delete this template?{/ts}');">
                      <i class="fa fa-trash"></i>
                    </a>
                  </span>
              </td>
            </tr>
          {/foreach}
          </tbody>
        </table>
      </div>
    {else}
      <div class="messages status no-popup">
        <div class="icon inform-icon"></div>
        {ts}No templates found. Click "Add New Template" to create your first membership card template.{/ts}
      </div>
    {/if}
  </div>
{/if}
