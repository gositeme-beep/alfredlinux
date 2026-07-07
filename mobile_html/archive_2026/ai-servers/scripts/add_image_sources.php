<?php
/**
 * Add imageSourceUrl (real product image URLs) to all products.
 * Run once: php add_image_sources.php
 * Then run: php pull_product_images.php
 */
$baseDir = dirname(__DIR__);
$dataDir = $baseDir . '/data';

$sources = [
    'gpus' => [
        // GeForce: mix Strix and Founders Edition so options grid doesn’t look like the same card repeated
        'gpu-rtx5060' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/23/NVIDIA_RTX_4090_Founders_Edition_-_Nahaufnahme_%28ZMASLO%29.png/500px-NVIDIA_RTX_4090_Founders_Edition_-_Nahaufnahme_%28ZMASLO%29.png',
        'gpu-rtx5070' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/23/NVIDIA_RTX_4090_Founders_Edition_-_Nahaufnahme_%28ZMASLO%29.png/500px-NVIDIA_RTX_4090_Founders_Edition_-_Nahaufnahme_%28ZMASLO%29.png',
        'gpu-rtx5080' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/76/Asus_Strix_RTX_4090.jpg/500px-Asus_Strix_RTX_4090.jpg',
        'gpu-rtx5090' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/76/Asus_Strix_RTX_4090.jpg/500px-Asus_Strix_RTX_4090.jpg',
        'gpu-rtx4090' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/76/Asus_Strix_RTX_4090.jpg/500px-Asus_Strix_RTX_4090.jpg',
        // Workstation: Quadro-style so they look different from GeForce
        'gpu-rtx-pro-4000' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d5/NVIDIA_Quadro_K6000.jpg/500px-NVIDIA_Quadro_K6000.jpg',
        'gpu-rtx-pro-5000' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d5/NVIDIA_Quadro_K6000.jpg/500px-NVIDIA_Quadro_K6000.jpg',
        'gpu-rtx-pro-6000' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d5/NVIDIA_Quadro_K6000.jpg/500px-NVIDIA_Quadro_K6000.jpg',
        'gpu-amd-rx7900xtx' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Sapphire_AMD_Radeon_RX_7900_XTX.jpg/500px-Sapphire_AMD_Radeon_RX_7900_XTX.jpg',
        'gpu-amd-mi300' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Sapphire_AMD_Radeon_RX_7900_XTX.jpg/500px-Sapphire_AMD_Radeon_RX_7900_XTX.jpg',
    ],
    'cpus' => [
        'cpu-ryzen9-9950x' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/25/AMD_Ryzen_9_9950X.jpg/500px-AMD_Ryzen_9_9950X.jpg',
        'cpu-intel-i9-14900k' => 'https://upload.wikimedia.org/wikipedia/commons/1/12/Intel_i9-14900K.webp',
        'cpu-threadripper-pro-7995wx' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/25/AMD_Ryzen_9_9950X.jpg/500px-AMD_Ryzen_9_9950X.jpg',
        'cpu-xeon-w7-3495x' => 'https://upload.wikimedia.org/wikipedia/commons/1/12/Intel_i9-14900K.webp',
    ],
    'motherboards' => [
        'mb-asus-x670e-hero' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/11/Supermicro_370DLE_ATX_motherboard_front.jpg/500px-Supermicro_370DLE_ATX_motherboard_front.jpg',
        'mb-asus-z790-dark' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/11/Supermicro_370DLE_ATX_motherboard_front.jpg/500px-Supermicro_370DLE_ATX_motherboard_front.jpg',
        'mb-asus-trx50-sage' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/11/Supermicro_370DLE_ATX_motherboard_front.jpg/500px-Supermicro_370DLE_ATX_motherboard_front.jpg',
        'mb-asus-w790-sage' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/11/Supermicro_370DLE_ATX_motherboard_front.jpg/500px-Supermicro_370DLE_ATX_motherboard_front.jpg',
    ],
    'ram' => [
        'ram-64gb-ddr5-6000' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c0/HyperX_Fury_DDR4-RAM_20210611_Vorderseite.png/500px-HyperX_Fury_DDR4-RAM_20210611_Vorderseite.png',
        'ram-128gb-ddr5-5600' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c0/HyperX_Fury_DDR4-RAM_20210611_Vorderseite.png/500px-HyperX_Fury_DDR4-RAM_20210611_Vorderseite.png',
        'ram-256gb-ddr5-ecc' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c0/HyperX_Fury_DDR4-RAM_20210611_Vorderseite.png/500px-HyperX_Fury_DDR4-RAM_20210611_Vorderseite.png',
        'ram-512gb-ddr5-ecc' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c0/HyperX_Fury_DDR4-RAM_20210611_Vorderseite.png/500px-HyperX_Fury_DDR4-RAM_20210611_Vorderseite.png',
    ],
    'storage' => [
        'ssd-2tb-nvme-gen4' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ed/1TB_2280_NVME_SSD.jpg/500px-1TB_2280_NVME_SSD.jpg',
        'ssd-4tb-nvme-gen4' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ed/1TB_2280_NVME_SSD.jpg/500px-1TB_2280_NVME_SSD.jpg',
        'ssd-4tb-nvme-gen5' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ed/1TB_2280_NVME_SSD.jpg/500px-1TB_2280_NVME_SSD.jpg',
    ],
    'psus' => [
        'psu-1000w-gold' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/ATX_Computer_power_supply_unit.jpg/500px-ATX_Computer_power_supply_unit.jpg',
        'psu-1200w-platinum' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/ATX_Computer_power_supply_unit.jpg/500px-ATX_Computer_power_supply_unit.jpg',
        'psu-1600w-platinum' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/ATX_Computer_power_supply_unit.jpg/500px-ATX_Computer_power_supply_unit.jpg',
    ],
    'cases' => [
        'case-atx-mid' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/18/NZXT_H500i_case_empty_10-13-2018.jpg/500px-NZXT_H500i_case_empty_10-13-2018.jpg',
        'case-atx-full' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/43/Cooler_Master_690_II_Advanced_Nvidia_Edition_Mid_Tower.jpg/500px-Cooler_Master_690_II_Advanced_Nvidia_Edition_Mid_Tower.jpg',
        'case-workstation' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/43/Cooler_Master_690_II_Advanced_Nvidia_Edition_Mid_Tower.jpg/500px-Cooler_Master_690_II_Advanced_Nvidia_Edition_Mid_Tower.jpg',
    ],
];

foreach ($sources as $cat => $urls) {
    $file = $dataDir . '/' . $cat . '.json';
    if (!is_file($file)) continue;
    $list = json_decode(file_get_contents($file), true);
    if (!is_array($list)) continue;
    foreach ($list as &$p) {
        $id = isset($p['id']) ? $p['id'] : '';
        if (isset($urls[$id])) {
            $p['imageSourceUrl'] = $urls[$id];
        }
    }
    unset($p);
    file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Updated $cat\n";
}

echo "Done. Now run: php pull_product_images.php\n";
