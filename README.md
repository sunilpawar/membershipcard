# CiviCRM Membership Card Extension

A comprehensive membership card designer extension for CiviCRM with drag-and-drop functionality, token support, and photo integration.

## Features

- **Visual Card Designer**: Drag-and-drop interface with Fabric.js
- **Token System**: Dynamic content with contact, membership, and organization tokens
- **Photo Support**: Upload and position photos on cards
- **QR Codes & Barcodes**: Automatic generation for verification
- **Multiple Templates**: Create and manage multiple card designs
- **Grid Alignment**: Snap-to-grid for precise positioning
- **Layer Management**: Bring to front, send to back, duplicate elements
- **Preview System**: Real-time preview with sample data
- **Export Options**: PNG, PDF export capabilities
- **Responsive Design**: Works on desktop and mobile devices
- **Verification System**: QR code-based membership verification

## Installation

### Requirements

- CiviCRM 5.60 or higher
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.3
- Web server with file upload support

### Step 1: Download Extension

```bash
cd /path/to/civicrm/extensions
git clone https://github.com/sunilpawar/membershipcard.git com.skvare.membershipcard
```

Or download the zip file and extract to your extensions directory.

### Step 2: Install Extension

1. Go to **Administer** → **System Settings** → **Extensions**
2. Find "Membership Card Designer" in the list
3. Click **Install**

### Step 3: Configure Permissions

Go to **Administer** → **Users and Permissions** → **Permissions (Access Control)**

Grant the following permissions:
- `access CiviMember` - View membership cards
- `administer CiviCRM` - Create and manage templates
- `edit memberships` - Generate cards for memberships

## Usage

### Creating Card Templates

1. Navigate to **Memberships** → **Card Templates**
2. Click **Add New Template**
3. Use the visual designer to create your card:
  - **Add Elements**: Use toolbar buttons to add text, images, QR codes, barcodes
  - **Drag Tokens**: Drag tokens from left panel to card
  - **Position Elements**: Drag elements to desired positions
  - **Edit Properties**: Select elements and modify in right panel
  - **Set Background**: Choose colors or upload background images

### Available Tokens

#### Contact Information
- `{contact.display_name}` - Full Name
- `{contact.first_name}` - First Name
- `{contact.last_name}` - Last Name
- `{contact.email}` - Email Address
- `{contact.phone}` - Phone Number
- `{contact.street_address}` - Street Address
- `{contact.city}` - City
- `{contact.state_province}` - State/Province
- `{contact.postal_code}` - Postal Code
- `{contact.image_URL}` - Contact Photo

#### Membership Details
- `{membership.membership_type}` - Membership Type
- `{membership.status}` - Membership Status
- `{membership.start_date}` - Start Date
- `{membership.end_date}` - End Date
- `{membership.join_date}` - Join Date
- `{membership.membership_id}` - Membership ID
- `{membership.source}` - Membership Source

#### Organization Data
- `{organization.organization_name}` - Organization Name
- `{organization.organization_logo}` - Organization Logo
- `{organization.organization_address}` - Organization Address
- `{organization.organization_phone}` - Organization Phone
- `{organization.organization_email}` - Organization Email

#### System Tokens
- `{system.current_date}` - Current Date
- `{system.qr_code}` - QR Code for verification
- `{system.barcode}` - Barcode with membership ID

### Generating Cards

#### For Individual Memberships
1. Go to a contact's membership tab
2. Click **Generate Card** next to the membership
3. Select a template
4. Click **Generate** to create the card
5. Download or print the generated card

#### Bulk Generation
1. Go to **Search** → **Find Memberships**
2. Select desired memberships
3. Choose **Actions** → **Generate Membership Cards**
4. Select template and generate

### Card Verification

Cards include QR codes that link to a verification page:
- Scan QR code with any QR reader
- Verification page shows membership status and validity
- Perfect for event check-ins and access control

## Configuration

### Template Settings

Each template can be configured with:
- **Card Dimensions**: Width and height in pixels
- **Background**: Color or image
- **Elements**: Text, images, QR codes, barcodes
- **Styling**: Fonts, colors, positioning

### Recommended Card Sizes

- **Standard Business Card**: 350px × 220px (3.5" × 2.2")
- **Credit Card Size**: 340px × 214px (85.6mm × 53.98mm)
- **Large Card**: 400px × 250px (4" × 2.5")
- **Square Card**: 300px × 300px

### Photo Guidelines

- **Format**: JPG, PNG, GIF
- **Size**: Maximum 2MB per image
- **Resolution**: 300 DPI recommended for print quality
- **Dimensions**: Photos will be automatically scaled to fit

### Background Images

- **Format**: JPG, PNG
- **Size**: Maximum 5MB
- **Resolution**: Match your card dimensions for best quality
- **Transparency**: PNG format supports transparent backgrounds

## API Usage

### Generate Card via API

```php
$result = civicrm_api3('MembershipCard', 'generate', [
  'membership_id' => 123,
  'template_id' => 1,
]);
```

### Get Cards for Contact

```php
$cards = civicrm_api3('MembershipCard', 'getbycontact', [
  'contact_id' => 456,
]);
```

## Troubleshooting

### Common Issues

#### Card Generation Fails
- Check PHP memory limit (recommended: 256MB+)
- Verify file upload permissions
- Ensure GD library is installed for image processing

#### Images Not Displaying
- Check file paths and permissions
- Verify image formats are supported
- Ensure images are within size limits

#### Template Designer Not Loading
- Check JavaScript console for errors
- Verify Fabric.js library is loading
- Clear browser cache

#### QR Codes Not Working
- Verify QR code generation library is available
- Check URL accessibility for verification links
- Test QR codes with multiple readers


### Performance Optimization

#### Large Organizations
- Enable template caching
- Optimize image sizes
- Use CDN for static assets
- Consider batch processing for bulk generation

## Integration Examples

### Event Check-in Integration

```php
// Verify membership at event check-in
function verifyMembershipCard($qrData) {
  $membershipId = extractMembershipId($qrData);
  $result = civicrm_api3('MembershipCard', 'verify', [
    'id' => $membershipId
  ]);

  if ($result['is_valid']) {
    // Grant access
    recordAttendance($membershipId);
    return true;
  }

  return false;
}
```

### Email Integration

```php
// Send card via email
function emailMembershipCard($contactId, $templateId) {
  $memberships = civicrm_api3('Membership', 'get', [
    'contact_id' => $contactId,
    'status_id' => 'Current',
  ]);

  foreach ($memberships['values'] as $membership) {
    $card = civicrm_api3('MembershipCard', 'generate', [
      'membership_id' => $membership['id'],
      'template_id' => $templateId,
    ]);

    // Send email with card attachment
    sendEmailWithCard($contactId, $card['download_url']);
  }
}
```


## Advanced Features

### Custom Verification Logic

```php
function custom_membership_verification($membershipId) {
  // Custom business logic
  $membership = getMembership($membershipId);

  // Check additional criteria
  if (hasOutstandingDues($membership)) {
    return ['is_valid' => false, 'reason' => 'Outstanding dues'];
  }

  if (isTemporarilySuspended($membership)) {
    return ['is_valid' => false, 'reason' => 'Temporarily suspended'];
  }

  return ['is_valid' => true];
}
```

### Batch Processing

For large organizations, implement batch card generation:

```php
function batchGenerateCards($membershipIds, $templateId) {
  $queue = CRM_Queue_Service::singleton()->create([
    'type' => 'Sql',
    'name' => 'membershipcard_batch',
    'reset' => FALSE,
  ]);

  foreach ($membershipIds as $membershipId) {
    $queue->createItem(
      new CRM_Queue_Task(
        ['CRM_Membershipcard_Task_Generate', 'run'],
        [$membershipId, $templateId]
      )
    );
  }

  return $queue;
}
```

## Support and Contributing

### Reporting Issues
When reporting issues, include:
- CiviCRM version
- Extension version
- PHP version
- Browser and version (for designer issues)
- Steps to reproduce
- Error messages or screenshots

### Contributing
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request


## Changelog

### Version 1.0.0 (2024-08-03)
- Initial release
- Drag-and-drop card designer
- Token system for dynamic content
- Photo and image support
- QR code and barcode generation
- Template management
- Card verification system
- Export functionality

### Roadmap

#### Version 1.1.0 (Planned)
- PDF export support
- Advanced template sharing
- Bulk template operations
- Enhanced mobile support

#### Version 1.2.0 (Planned)
- Advanced QR code features
- Custom verification workflows
- Multi-organization support

## License

This extension is licensed under [AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0.en.html).

## Credits

- Built with [Fabric.js](http://fabricjs.com/) for canvas manipulation
- Uses [Font Awesome](https://fontawesome.com/) for icons
- Inspired by Wild Apricot's membership card features
- Thanks to the CiviCRM community for feedback and testing
