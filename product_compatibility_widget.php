<?php
// Widget to show compatible models for a product
function renderCompatibilityWidget($product_id, $db) {
    $query = "SELECT DISTINCT mk.name as make_name, cm.name as model_name, cm.year_from, cm.year_to
              FROM product_compatibility pc
              JOIN car_models cm ON pc.car_model_id = cm.id
              JOIN car_makes mk ON cm.make_id = mk.id
              WHERE pc.product_id = ?
              ORDER BY mk.name, cm.name";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$product_id]);
    $compatible_models = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($compatible_models)) {
        return '<p style="color: var(--gray-500); font-style: italic;">Compatibility information not available.</p>';
    }
    
    $html = '<div style="background: var(--gray-50); border-radius: var(--border-radius); padding: 1rem; margin: 1rem 0;">';
    $html .= '<h4 style="margin-bottom: 1rem; color: var(--gray-900); display: flex; align-items: center; gap: 0.5rem;">';
    $html .= '<i class="fas fa-check-circle" style="color: var(--success-color);"></i> Compatible Vehicles';
    $html .= '</h4>';
    
    $grouped_models = [];
    foreach ($compatible_models as $model) {
        $grouped_models[$model['make_name']][] = $model;
    }
    
    $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">';
    
    foreach ($grouped_models as $make => $models) {
        $html .= '<div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--border-radius); padding: 1rem;">';
        $html .= '<h5 style="color: var(--primary-color); margin-bottom: 0.5rem; font-weight: 700;">' . htmlspecialchars($make) . '</h5>';
        
        foreach ($models as $model) {
            $year_range = '';
            if ($model['year_from'] && $model['year_to']) {
                $year_range = ' (' . $model['year_from'] . '-' . $model['year_to'] . ')';
            }
            $html .= '<div style="color: var(--gray-700); font-size: 0.875rem; margin-bottom: 0.25rem;">';
            $html .= '<i class="fas fa-car" style="color: var(--gray-400); margin-right: 0.5rem;"></i>';
            $html .= htmlspecialchars($model['model_name']) . $year_range;
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// Function to get compatibility count for a product
function getCompatibilityCount($product_id, $db) {
    $query = "SELECT COUNT(*) FROM product_compatibility WHERE product_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$product_id]);
    return $stmt->fetchColumn();
}

// Function to check if a product is universal (compatible with 4+ models)
function isUniversalPart($product_id, $db) {
    return getCompatibilityCount($product_id, $db) >= 4;
}
?>
