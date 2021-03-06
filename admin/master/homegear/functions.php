<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////
//
////////////////////////////////////////////////////////////////////////////////////////////////////////
function homegear_init() {
    global $interfaceData;

    if(!$_SERVER['WEBSOCKET_ENABLED']) die('WebSockets are not enabled on this server in "rpcservers.conf".');
    if($_SERVER['WEBSOCKET_AUTH_TYPE'] != 'session') die('WebSocket authorization type is not set to "session" in "rpcservers.conf".');

    $hg = new \Homegear\Homegear();

    try {
        $hg_lang     = $interfaceData["options"]["language"] ?? 'en-US';
        $hg_ui_elems = $hg->getAllUiElements($hg_lang);
        $hg_floors   = $hg->getStories($hg_lang);
        $hg_rooms    = $hg->getRooms($hg_lang);
        $hg_roles    = $hg->getRoles($hg_lang);
    }
    catch (\Homegear\HomegearException $e) {
        die( $hg->log(2, 'Homegear Exception catched. ' .
                               "Code: {$e->getCode()} " .
                            "Message: {$e->getMessage()}")
        );
    }

    function array_move_element($key, &$from, &$dest) {
        $dest[$key] = $from[$key];
        unset($from[$key]);
    }

    function floor_parse(&$house, &$floor) {
        $id = $floor['ID'];

        $house['floors'][$id] = [
            'name'  => $floor['NAME'],
            'rooms' => $floor['ROOMS'],
        ];

        foreach ($floor['METADATA'] as $name => &$data)
            $house['floors'][$id][$name] = $data;
    }

    function room_parse(&$house, &$room) {
        $id = $room['ID'];

        $house['rooms'][$id] = [
            'devices' => [],
            'floors'  => [],
            'name'    => $room['NAME'],
        ];

        foreach ($room['METADATA'] as $name => &$data)
            $house['rooms'][$id][$name] = $data;
    }

    function device_is_simple(&$dev) {
        return $dev['type'] == 'simple';
    }

    // Move the values key-value-pairs into their respective properties
    function device_values_into_props(&$dev) {
        foreach ($dev['controls'] as &$control)
            foreach ($control['variableInputs'] as &$input)
                if (array_key_exists('value', $input))
                    array_move_element('value', $input, $input['properties']);
    }

    function device_cleanup_type(&$dev) {
        foreach ($dev['controls'] as &$control)
            unset($control['type']);

        unset($dev['type']);
    }

    function device_cleanup_language_disabled(&$dev, $lang) {
        if (! array_key_exists('metadata', $dev) ||
            ! array_key_exists('event_hooks', $dev['metadata']))
            return;

        $event_hooks = &$dev['metadata']['event_hooks'];
        foreach ($event_hooks as &$event) {
            if (! array_key_exists('translations', $event))
                continue;

            $trans    = &$event['translations'];
            $lang_sel = array_key_exists($lang, $trans) ? $lang : 'en-US';

            $event['texts'] = $trans[$lang_sel];
            unset($event['translations']);
        }
    }

    function device_make_complex($dev) {
        // Create an empty grid frame
        $dev['grid'] = null;
        $dev['controls'][]['cell'] = null;

        $fields_to_move = [
            'control', 'uniqueUiElementId', 'variableInputs', 'variableOutputs'
        ];
        foreach ($fields_to_move as $field)
            array_move_element($field, $dev, $dev['controls'][0]);

        return $dev;
    }

    function device_build_invoke_map(&$map, $dev, $id) {
        foreach ($dev['controls'] as $key_control => $control) {
            foreach ($control['variableInputs'] as $key_input => $input) {
                $roles = $input['roles'] ?? array();
                $map[$input['peer']]
                    [$input['channel']]
                    [$input['name']][] = [
                        'databaseId' => $id,
                        'control'    => $key_control,
                        'input'      => $key_input,
                        'roles'      => $roles
                ];
            }

            foreach ($control['variableOutputs'] as $key_output => $output) {
                $roles = $output['roles'] ?? array();
                $map[$output['peer']]
                    [$output['channel']]
                    [$output['name']][] = [
                        'databaseId' => $id,
                        'control'    => $key_control,
                        'input'      => $key_input,
                        'roles'      => $roles
                ];
            }
        }
    }

    function device_parse(&$house, &$map_invoke, &$dev, $lang) {
        $id = $dev['databaseId'];

        // Push device into the room it is located in
        $house['rooms'][$dev['room']]['devices'][] = $id;

        if (device_is_simple($dev))
            $dev = device_make_complex($dev);

        device_values_into_props($dev);
        device_cleanup_type($dev);
        device_cleanup_language_disabled($dev, $lang);

        device_build_invoke_map($map_invoke, $dev, $id);

        $house['devices'][$id] = $dev;
    }

    function house_build_back_refs(&$house) {
        foreach ($house['floors'] as $id_floor => &$floor) {
            foreach ($floor['rooms'] as $id_room) {
                $house['rooms'][$id_room]['floors'][] = $id_floor;

                foreach ($house['rooms'][$id_room]['devices'] as $id_dev) {
                    $house['devices'][$id_dev]['floors'][] = $id_floor;
                    $house['devices'][$id_dev]['rooms'][] = $id_room;
                }
            }
        }
    }

    function mainmenu_parse() {
        global $interfaceData;
        foreach ($interfaceData["mainmenu"] as $key => $value) {
            if ($value["name"] == "") {
                unset($interfaceData["mainmenu"][$key]);
            }
        }
        return $interfaceData["mainmenu"];
    }

    function menu_parse() {
        global $interfaceData;
        foreach ($interfaceData["menu"] as $key => $value) {
            if ($value["name"] == "") {
                unset($interfaceData["menu"][$key]);
            }
        }
        return $interfaceData["menu"];
    }

    $house = [
        'devices'      => [],
        'floors'       => [],
        'rooms'        => [],
        'roles'        => [],
        'mainmenu'     => mainmenu_parse(),
        'menu'         => menu_parse(),
        'themes'       => $interfaceData["themes"],
        'options'      => $interfaceData["options"],
        'iconFallback' => $interfaceData["iconFallback"],
    ];

    if($hg_lang != "en-US"){
        $i18nOut = $interfaceData["i18n"][$hg_lang];
        $i18nOut["default"] = $interfaceData["i18n"]["en-US"];
    }
    else{
        $i18nOut = $interfaceData["i18n"]["en-US"];
    }

    $house["i18n"] = $i18nOut;

    // will be filled while deviceparsing
    $map_invoke = [];

    foreach($hg_roles as $key => $value){
        $aggregated = $hg->aggregateRoles(2, $value["ID"], array());
        $varInRole = $hg->getVariablesInRole($value["ID"]);
        if($aggregated["variableCount"] > 0 || isset($value["METADATA"]["ui"])){
            $house['roles'][$value["ID"]]["name"] = $value["NAME"];
            if(isset($value["METADATA"]["ui"]) && is_array($value["METADATA"]["ui"]["translations"]) && array_key_exists($hg_lang, $value["METADATA"]["ui"]["translations"])){
                $house['roles'][$value["ID"]]["texts"] = $value["METADATA"]["ui"]["translations"][$hg_lang];
                unset($value["METADATA"]["ui"]["translations"]);
            }
            else if(isset($value["METADATA"]["ui"]) && is_array($value["METADATA"]["ui"]["translations"])){
                $house['roles'][$value["ID"]]["texts"] = $value["METADATA"]["ui"]["translations"]["en-US"];
                unset($value["METADATA"]["ui"]["translations"]);
            }
            if(isset($value["METADATA"]["ui"]) && isset($value["METADATA"]["ui"]["label"]) && array_key_exists($hg_lang, $value["METADATA"]["ui"]["label"])){
                $house['roles'][$value["ID"]]["name"] = $value["METADATA"]["ui"]["label"][$hg_lang];
                unset($value["METADATA"]["ui"]["label"]);
            }
            else if(isset($value["METADATA"]["ui"]) && isset($value["METADATA"]["ui"]["label"])){
                $house['roles'][$value["ID"]]["name"] = $value["METADATA"]["ui"]["label"]["en-US"];
                unset($value["METADATA"]["ui"]["label"]);
            }
            if(is_array($house['roles'][$value["ID"]]) && isset($value["METADATA"]["ui"]) && is_array($value["METADATA"]["ui"])){
                $house['roles'][$value["ID"]] = array_replace_recursive($house['roles'][$value["ID"]], $value["METADATA"]["ui"]);
            }
            $house['roles'][$value["ID"]]["aggregated"] = $aggregated;
            $house['roles'][$value["ID"]]["varInRole"] = $varInRole;
        }
    }

    foreach ($hg_floors as &$floor)
        floor_parse($house, $floor);

    foreach ($hg_rooms as &$room)
        room_parse($house, $room);

    foreach ($hg_ui_elems as &$dev)
        device_parse($house, $map_invoke, $dev, $hg_lang);

    // Insert the cross references
    house_build_back_refs($house);

    $house["map_invoke"] = $map_invoke;

    return $house;
}
