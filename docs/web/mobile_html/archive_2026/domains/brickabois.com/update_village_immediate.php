<?php
/**
 * Immediate Update Script - Change Village Lanaudière to Sainte-Émélie-de-l'Énergie
 * Run this once to update the database
 */

require_once __DIR__ . '/config.php';

try {
    $db = getDBConnection();
    
    // Update existing village
    $stmt = $db->prepare("
        UPDATE `villages` 
        SET 
            `name` = 'Sainte-Émélie-de-l\'Énergie',
            `name_fr` = 'Sainte-Émélie-de-l\'Énergie',
            `slug` = 'sainte-emelie-de-lenergie',
            `description` = 'The founding village of the Free Village Network, embodying freedom grounded in place, culture, and stewardship. Located in the heart of Lanaudière.',
            `description_fr` = 'Le village fondateur du Réseau des Villages Libres, incarnant la liberté ancrée dans le lieu, la culture et la gérance. Situé au cœur de la Lanaudière.',
            `location_lat` = 46.3167,
            `location_lng` = -73.6333,
            `location_address` = 'Sainte-Émélie-de-l\'Énergie, QC, Canada',
            `region` = 'Lanaudière',
            `status` = 'active',
            `founded_date` = CURDATE()
        WHERE `slug` = 'lanaudiere' OR `name` LIKE '%Lanaudière%' OR `name` LIKE '%Lanaudiere%'
    ");
    
    $stmt->execute();
    $affected = $stmt->rowCount();
    
    if ($affected > 0) {
        echo "✓ Updated $affected village record(s) to Sainte-Émélie-de-l'Énergie\n";
    } else {
        // Insert if doesn't exist
        $stmt = $db->prepare("
            INSERT INTO `villages` 
            (`name`, `name_fr`, `slug`, `description`, `description_fr`, `location_lat`, `location_lng`, `location_address`, `region`, `country`, `status`, `founded_date`) 
            VALUES (
                'Sainte-Émélie-de-l\'Énergie',
                'Sainte-Émélie-de-l\'Énergie',
                'sainte-emelie-de-lenergie',
                'The founding village of the Free Village Network, embodying freedom grounded in place, culture, and stewardship. Located in the heart of Lanaudière.',
                'Le village fondateur du Réseau des Villages Libres, incarnant la liberté ancrée dans le lieu, la culture et la gérance. Situé au cœur de la Lanaudière.',
                46.3167,
                -73.6333,
                'Sainte-Émélie-de-l\'Énergie, QC, Canada',
                'Lanaudière',
                'Canada',
                'active',
                CURDATE()
            )
        ");
        $stmt->execute();
        echo "✓ Created new village record for Sainte-Émélie-de-l'Énergie\n";
    }
    
    // Verify
    $stmt = $db->prepare("SELECT id, name, name_fr, slug FROM villages WHERE slug = 'sainte-emelie-de-lenergie' OR name LIKE '%Sainte-Émélie%'");
    $stmt->execute();
    $village = $stmt->fetch();
    
    if ($village) {
        echo "\n✓ Verification: Village found:\n";
        echo "  ID: " . $village['id'] . "\n";
        echo "  Name: " . $village['name'] . "\n";
        echo "  Name FR: " . $village['name_fr'] . "\n";
        echo "  Slug: " . $village['slug'] . "\n";
    } else {
        echo "\n✗ Warning: Village not found after update\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✓ Done! The founding village is now Sainte-Émélie-de-l'Énergie\n";
?>

