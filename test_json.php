<?php
$params = ['dropoff' => []];

if (empty($params['pickup']) && empty($params['dropoff']) && empty($params['non_integrated'])) {
    $params['pickup'] = new \stdClass();
} else {
    if (isset($params['dropoff']) && is_array($params['dropoff']) && empty($params['dropoff'])) {
        $params['dropoff'] = new \stdClass();
    }
    if (isset($params['pickup']) && is_array($params['pickup']) && empty($params['pickup'])) {
        $params['pickup'] = new \stdClass();
    }
}

$body = array_merge(['order_sn' => '123'], $params);
echo json_encode($body) . "\n";
