# 📦 Warehouse Barcode Scanner - Complete Usage Guide

## Overview

This plugin is designed for **warehouse inventory import** - allowing staff to scan product barcodes directly from product packaging and instantly import them into WooCommerce as products with full details.

## 🎯 Use Case: Warehouse Inventory Import

### Scenario
Your warehouse has thousands of products in packages with printed barcodes. You need to:
1. Scan each product's barcode
2. Automatically fetch product details (name, description, image)
3. Create WooCommerce products in your database
4. Track what's been scanned in your session

### Solution
This plugin provides a mobile-optimized scanner that:
- ✅ Opens device camera
- ✅ Continuously scans barcodes
- ✅ Fetches product info from external databases
- ✅ Creates WooCommerce products automatically
- ✅ Tracks session statistics
- ✅ Prevents duplicate imports
- ✅ Works offline-friendly with basic fallback

---

## 📱 Setup for Warehouse Use

### Requirements
- WordPress site with HTTPS (required for camera access)
- WooCommerce plugin active
- Mobile device (smartphone/tablet) with camera
- Chrome (Android) or Safari (iOS) browser
- Internet connection for API lookups

### Installation
```bash
1. Upload plugin to: wp-content/plugins/wc-barcode-product-importer/
2. Activate plugin in WordPress admin
3. Navigate to: Warehouse Scanner menu
```

### Staff Access
```php
// Grant warehouse staff access
// Add this to functions.php or custom plugin:

function grant_warehouse_scanner_access() {
    $role = get_role('shop_manager');
    $role->add_cap('manage_woocommerce');
}
add_action('init', 'grant_warehouse_scanner_access');
```

---

## 🔄 Warehouse Workflow

### Step-by-Step Process

**1. Open Scanner (Mobile Device)**
- Login to WordPress admin on mobile
- Go to: `Warehouse Scanner` menu
- Click "Start Scanning" button

**2. Scan Products**
```
Point camera at barcode → Auto-detects → Beep sound → Product imported
```
- Hold phone 6-12 inches from barcode
- Ensure good lighting
- Keep barcode flat and clear
- Wait for beep confirmation

**3. Continuous Scanning**
- After successful scan, automatically ready for next
- 2-second cooldown between scans
- Real-time feedback on screen
- No need to press any buttons

**4. Monitor Progress**
- **Session Scans**: Total products scanned
- **Imported**: Successfully created products
- **Duplicates**: Already exist in database
- **Errors**: Failed imports (network/API issues)

**5. Review Imported Products**
- All products created as **DRAFT** status
- Go to: `Products → All Products`
- Filter by "Draft" status
- Review and edit:
  - Add correct pricing
  - Update stock quantity
  - Verify description
  - Check images
  - Publish when ready

---

## 📊 What Gets Imported

### Product Data Structure

```javascript
{
  name: "Product Name from API",
  sku: "1234567890123", // The barcode
  regular_price: "0.00", // Must be updated manually
  description: "Full product description...",
  short_description: "Brief description",
  image_url: "https://...", // Auto-downloaded
  brand: "Brand Name",
  weight: "500g",
  categories: ["Food", "Beverages"],
  status: "draft" // For review
}
```

### WooCommerce Product Fields Set

| Field | Value | Notes |
|-------|-------|-------|
| Product Name | From API | Editable |
| SKU | Barcode Number | Unique identifier |
| Regular Price | 0.00 | **Must be updated** |
| Description | From API | Full details |
| Short Description | From API | Summary |
| Image | Downloaded | Main product image |
| Status | Draft | Ready for review |
| Stock | 0 (Out of stock) | **Must be updated** |
| Manage Stock | Yes | Enabled |
| Brand (Meta) | From API | Custom field |
| Import Date (Meta) | Current date | Tracking |
| Import User (Meta) | Current user | Tracking |

---

## 🔍 Product Data Sources

### Primary: Open Food Facts API
- **Coverage**: Food & beverage products worldwide
- **Free**: No API key required
- **Data**: Name, ingredients, images, allergens, nutrition
- **Database**: 2.5+ million products

### Fallback: Basic Entry
If product not found in API:
```
Name: "Product [Barcode]"
Description: "Product imported from warehouse scan on [Date]"
Status: Draft
Image: None (manual upload required)
```

### Future: Custom API Integration

You can replace the API source in the plugin:

```php
// In wc-barcode-product-importer.php
protected function fetch_product_from_api($barcode)
{
    // Replace with your ERP/PIM system API
    $response = wp_remote_get("https://your-erp-api.com/products/{$barcode}", [
        'headers' => [
            'Authorization' => 'Bearer YOUR_API_KEY',
            'Content-Type' => 'application/json'
        ]
    ]);
    
    // Parse your API response
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    // Return standardized format
    return [
        'name' => $data['product_name'],
        'sku' => $barcode,
        'regular_price' => $data['price'], // From your system
        'description' => $data['description'],
        'image_url' => $data['image_url'],
        'brand' => $data['manufacturer'],
        // ... more fields
    ];
}
```

---

## ⚡ Performance & Scale

### Scanning Speed
- **Average**: 2-3 seconds per product
- **Rate**: 20-30 products per minute
- **Session**: 500-1000 products per hour
- **Cooldown**: 2 seconds between scans (prevents duplicates)

### Database Impact
Each scan creates:
- 1 WooCommerce product (post)
- 10-15 post meta entries
- 1 product image (if available)
- Total: ~20KB per product

**Example Load:**
- 1000 products = ~20MB database growth
- Image storage = 100-500MB (depends on image sizes)

### Optimization Tips
```php
// Increase PHP memory for large imports
ini_set('memory_limit', '512M');

// Increase execution time
ini_set('max_execution_time', 300);

// Disable auto-drafts during import
define('AUTOSAVE_INTERVAL', 300);
```

---

## 🛠️ Warehouse Best Practices

### 1. **Pre-Import Planning**
- [ ] Create product categories in advance
- [ ] Set up default product attributes
- [ ] Prepare pricing spreadsheet
- [ ] Test with 10-20 products first

### 2. **During Scanning Session**
- [ ] Good lighting essential
- [ ] Clean barcodes (no damage/wrinkles)
- [ ] Scan in batches (100-200 products)
- [ ] Take breaks every 30 minutes
- [ ] Monitor session statistics
- [ ] Note any errors immediately

### 3. **Post-Import Workflow**
```
1. Go to Products → All Products → Filter: Draft
2. Bulk edit required fields:
   - Regular Price
   - Sale Price (if applicable)
   - Stock Quantity
   - Stock Status → In Stock
3. Verify images loaded correctly
4. Assign to proper categories
5. Publish in batches
```

### 4. **Quality Control Checklist**
- [ ] All products have correct prices
- [ ] Images are appropriate quality
- [ ] Descriptions are accurate
- [ ] Stock quantities updated
- [ ] Categories assigned
- [ ] No duplicate SKUs

---

## 🔒 Security Features

### Built-in Protection
```php
✓ AJAX nonce verification
✓ Capability checks (manage_woocommerce)
✓ Data sanitization
✓ XSS prevention (escaped output)
✓ SQL injection prevention (prepared statements)
✓ Duplicate SKU detection
✓ Rate limiting (2-second cooldown)
```

### Access Control
Only users with `manage_woocommerce` capability can:
- Access scanner page
- Scan barcodes
- Create products
- View scan history

---

## 📈 Session Management

### Statistics Tracked
- **Total Scans**: All barcode scans attempted
- **Successful**: Products created successfully
- **Duplicates**: Products already in database
- **Errors**: Failed imports

### Session Data Storage
```php
// Stored in PHP session
$_SESSION['wc_bpi_scan_session'] = [
    'total_scans' => 45,
    'successful' => 38,
    'duplicates' => 5,
    'errors' => 2
];
```

### Clear Session
- Click "Clear Session" button
- Resets all counters
- Does NOT delete imported products
- Clears recent scans list

---

## 🐛 Troubleshooting

### Camera Not Working

**Issue**: Camera won't open
```
Solution:
1. Check HTTPS (required for camera API)
2. Grant browser camera permission
3. Close other apps using camera
4. Try different browser
5. Restart device
```

**Issue**: "BarcodeDetector not supported"
```
Solution:
- Use Chrome on Android 83+
- Use Safari on iOS 14+
- Desktop browsers have limited support
- Consider using mobile device
```

### Products Not Importing

**Issue**: "Failed to create product"
```
Checklist:
1. Check WooCommerce is active
2. Check user has manage_woocommerce capability
3. Check PHP error logs
4. Check available disk space
5. Check memory_limit in php.ini
6. Test with simple product manually
```

**Issue**: Products created but no image
```
Solution:
- Check internet connection
- Verify image URL is valid
- Check PHP allow_url_fopen setting
- Check write permissions on uploads folder
- Manually upload images later
```

### Duplicate Detection

**Issue**: "Product already exists" but I don't see it
```
Solution:
1. Search products by SKU (barcode)
2. Check trash/draft products
3. Search in all product statuses
4. May be in different variation
```

### API Issues

**Issue**: "Failed to fetch product data"
```
Checklist:
1. Check internet connection
2. API may be temporarily down
3. Product not in API database
4. Rate limit reached (rare with Open Food Facts)
5. Firewall blocking API access
```

---

## 📊 Reporting & Analytics

### Export Scan History

```php
// Add to functions.php for CSV export
function export_scan_history_csv() {
    global $wpdb;
    
    $results = $wpdb->get_results("
        SELECT 
            p.ID,
            p.post_title,
            pm.meta_value as sku,
            p.post_date,
            p.post_status
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm 
            ON p.ID = pm.post_id 
            AND pm.meta_key = '_sku'
        WHERE p.post_type = 'product'
        AND p.post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY p.post_date DESC
    ", ARRAY_A);
    
    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="scan-history.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'SKU', 'Date', 'Status']);
    
    foreach ($results as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
```

### Session Statistics Dashboard

Access real-time stats:
- Scans per hour
- Success rate
- Most common errors
- Import velocity
- Products pending review

---

## 🚀 Advanced Features (Future)

### Planned Enhancements

**1. Settings Page**
```
- Configure default category
- Set default price
- Choose API provider
- Custom field mapping
- Auto-publish options
```

**2. Bulk Operations**
```
- Scan multiple → Queue
- Review all → Bulk edit
- Bulk pricing update
- Bulk publish
```

**3. Inventory Management**
```
- Update stock on rescan
- Location tracking
- Warehouse zones
- Bin/shelf assignment
```

**4. Enhanced Reporting**
```
- Daily scan reports
- Staff productivity
- Error analysis
- Cost tracking
```

**5. Offline Mode**
```
- Queue scans when offline
- Sync when connection restored
- Local database caching
```

---

## 💡 Tips for Maximum Efficiency

### Hardware Setup
- **Device**: Latest iPhone/Android with good camera
- **Stand**: Tripod or fixed mount for consistent scanning
- **Lighting**: Bright overhead or ring light
- **Scanner Gun**: Bluetooth barcode scanner (faster alternative)

### Software Setup
```php
// Disable email notifications during bulk import
add_filter('woocommerce_email_enabled_new_order', '__return_false');

// Disable product feeds during import
add_filter('wpseo_sitemap_exclude_post_type', function($exclude, $post_type) {
    return $post_type === 'product' ? true : $exclude;
}, 10, 2);
```

### Team Workflow
1. **Scanner**: Scans 100-200 products
2. **Reviewer**: Reviews drafts, adds pricing
3. **Publisher**: Final check and publishes
4. **Manager**: Monitors statistics

---

## 📞 Support & Resources

### Documentation
- WordPress Codex: https://codex.wordpress.org/
- WooCommerce Docs: https://woocommerce.com/documentation/
- Open Food Facts API: https://world.openfoodfacts.org/api

### Getting Help
1. Check browser console for errors
2. Review PHP error logs
3. Test with different products
4. Clear cache and try again
5. Contact developer with error details

### Contributing
This is open-source software. Contributions welcome:
- Report bugs
- Suggest features
- Submit pull requests
- Improve documentation

---

## ✅ Quick Reference Card

```
┌─────────────────────────────────────┐
│    WAREHOUSE SCANNER QUICK GUIDE    │
├─────────────────────────────────────┤
│ 1. Start Scanner (mobile)           │
│ 2. Point at barcode                 │
│ 3. Wait for beep                    │
│ 4. Scan next product                │
│ 5. Monitor statistics               │
│ 6. Review drafts later              │
│                                     │
│ Products saved as DRAFT             │
│ SKU = Barcode number                │
│ Price = $0.00 (UPDATE REQUIRED)     │
│ Stock = 0 (UPDATE REQUIRED)         │
└─────────────────────────────────────┘
```

---

## 📄 License
GPL v2 or later - Free for commercial use
