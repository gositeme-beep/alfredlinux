-- Update the founding village to Sainte-Émélie-de-l'Énergie
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
WHERE `slug` = 'lanaudiere' OR `name` LIKE '%Lanaudière%';

-- If no village exists, insert it
INSERT INTO `villages` (`name`, `name_fr`, `slug`, `description`, `description_fr`, `location_lat`, `location_lng`, `location_address`, `region`, `country`, `status`, `founded_date`) 
SELECT 
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
WHERE NOT EXISTS (SELECT 1 FROM `villages` WHERE `slug` = 'sainte-emelie-de-lenergie');

