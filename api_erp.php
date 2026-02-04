<?php
// api_erp.php
header('Content-Type: application/json');

// --- 1. ตั้งค่าการเชื่อมต่อ ERPNext ---
// ใส่ IP ตามที่คุณแจ้งมา
$erp_url = "http://172.16.200.190"; 
$api_key = "5f2b01fbd30e772";       
$api_secret = "a2271ad0667b202"; 


// --- 2. รับค่าจาก Frontend ---
$doctype = $_GET['doctype'] ?? '';
$search = $_GET['search'] ?? '';

if (!$doctype || !$search) {
    echo json_encode([]);
    exit;
}

// --- 3. กำหนด Field ที่ต้องการดึง (Mapping) ---
$field_map = [
    'Supplier'    => 'supplier_name',    // Supplier -> supplier_name
    'Customer'    => 'customer_name',    // Customer -> customer_name
    'Endcustomer' => 'customer_name',    // Endcustomer -> customer_name (ตามที่คุณต้องการ)
];

// ถ้า Doctype ไหนไม่อยู่ใน map ให้ใช้ field 'name'
$target_field = $field_map[$doctype] ?? 'name';

// --- 4. สร้าง URL สำหรับค้นหา ---
$endpoint = $erp_url . "/api/resource/" . urlencode($doctype);

$params = [
    'fields' => json_encode([$target_field]), 
    'filters' => json_encode([[$target_field, "like", "%$search%"]]), 
    'limit_page_length' => 10
];

$url = $endpoint . "?" . http_build_query($params);

// --- 5. ยิง Request ด้วย CURL ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: token $api_key:$api_secret",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
curl_close($ch);

// --- 6. แปลงข้อมูลส่งกลับ ---
$result = json_decode($response, true);
$final_data = [];

if (isset($result['data']) && is_array($result['data'])) {
    foreach ($result['data'] as $row) {
        if (isset($row[$target_field])) {
            $final_data[] = [
                'name' => $row[$target_field]
            ];
        }
    }
}

echo json_encode(['data' => $final_data]);
?>