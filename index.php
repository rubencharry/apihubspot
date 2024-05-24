<?php

// Verifica si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lee la entrada cruda
    $input = file_get_contents('php://input');
    // Decodifica el JSON recibido
    $data = json_decode($input, true);

    // Extrae el dealId del campo objectId
    if (isset($data['objectId'])) {
        $dealId = $data['objectId'];

        $dealState = $data['propertyValue'];

        // Obtén la información de los contactos asociados al deal
        $contactInfo = getContactsForDeal($dealId, $dealState);

        // Combina la información del deal y de los contactos
        $combinedInfo = [
            'deal' => $data,
            'contacts' => $contactInfo,
        ];

        // Establece el encabezado de respuesta a JSON
        header('Content-Type: application/json');
        // Envía la respuesta en formato JSON
        echo json_encode($combinedInfo);
    } else {
        header('HTTP/1.1 400 Bad Request');
        echo 'objectId no encontrado en el JSON recibido';
    }
} else {
    // Respuesta para otros métodos HTTP
    header('HTTP/1.1 405 Method Not Allowed');
    echo 'Método no permitido';
}

function getContactsForDeal($dealId, $dealState) {
    $hubspotApiUrl = "https://api.hubapi.com/crm/v3/objects/deals/{$dealId}/associations/contacts";
    $apiKey = 'pat-na1-937387e4-4d2e-4a3f-a976-85619eaaa146'; // Asegúrate de guardar tu API key de forma segura

    // Inicializa cURL para obtener los contactIds asociados al deal
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $hubspotApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    // Verifica si la respuesta contiene la clave 'results'
    if (!isset($data['results'])) {
        return [];
    }

    $contactIds = array_column($data['results'], 'id');

    // Inicializa un array para almacenar la información de los contactos
    $contacts = [];
    
    // Obtén la información de cada contacto usando el contactId
    foreach ($contactIds as $contactId) {
        $contactUrl = "https://api.hubapi.com/crm/v3/objects/contacts/{$contactId}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $contactUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$apiKey}",
            "Content-Type: application/json"
        ]);
        $contactResponse = curl_exec($ch);
        curl_close($ch);
        
        $contactData = json_decode($contactResponse, true);
        
        // Añade la variable dealState al contacto
        $contactData['dealState'] = $dealState;
        
        $contacts[] = $contactData;
    }

    return $contacts;
}

