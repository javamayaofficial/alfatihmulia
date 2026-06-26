<?php
/** api/counter.php - data live counter (JSON) untuk refresh dinamis */
require_once __DIR__ . '/../core/core.php';
header('Content-Type: application/json; charset=utf-8');
$im = impact_data();
$out = ['ok'=>true, 'data'=>array_merge($im['derived'], ['tersalurkan'=>total_tersalurkan()])];
foreach ($im['manual'] as $k=>$m) $out['data'][$k] = $m['value'];
echo json_encode($out);
