<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////
// ARRAY PRINT
// Hilfsfunktion zur lesbaren Darstellung von PHP Arrays
////////////////////////////////////////////////////////////////////////////////////////////////////////
function print_array($result){
  echo "<pre>";
  print_r($result);
  echo "</pre>";
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// in_array_r rcursive lookup 
////////////////////////////////////////////////////////////////////////////////////////////////////////
function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// l18n
////////////////////////////////////////////////////////////////////////////////////////////////////////
function l18n($langKey){
    global $currentUser;
    global $interfaceData;
    $l18nOut = null;

    if(isset($currentUser["settings"]["language"]) && $currentUser["settings"]["language"] != "en-US"){
        $l18nOut = $interfaceData["l18n"][$currentUser["settings"]["language"]];
        $l18nOut["default"] = $interfaceData["l18n"]["en-US"];
    }
    else{
        $l18nOut = $interfaceData["l18n"]["en-US"];
    }

    if(array_key_exists($langKey, $l18nOut)){
        return $l18nOut[$langKey];
    }
    else if(isset($l18nOut["default"]) && array_key_exists($langKey, $l18nOut["default"])){
        return $l18nOut["default"][$langKey];
    }
    else{
        return "NoTranslation: ".$langKey;
    }
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// Tabs
////////////////////////////////////////////////////////////////////////////////////////////////////////
function tabs($tabs, $options){
  $out = "";
  $count = 0;

  $tabs = explode(";", $tabs);

  $tabsCount = count($tabs);
  $tabSize = 100 / $tabsCount;
  
  if($options != ""){
      $options = json_decode($options, true);
  }

  $out .= '
    <div id="tabs">
  ';

  foreach($tabs as $tab){
    if(strpos($tab, '*') !== false){
      $active = "active";
      $activeArrow = '<div class="tab_pfeil"><svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0" y="0" width="200" height="100" viewBox="0, 0, 200, 100"><g id="Ebene_1"><path d="M0,-0 L204,-0 L101.747,100.002" /></g></svg></div>';
    }
    else{
      $active = "";
      $activeArrow = "";
    }

    $tab = str_replace("*", "", $tab);
    $tabID = preg_replace('/[^a-zA-Z0-9\']/', '_', $tab);
    $tabID = strtolower($tabID);

    if(is_array($options)){
        $id = 'id="'.$options['id'].'_'.$tabID.'"';
    }
    else{
        $id = "";
    }
    
    $out .= '
      <div '.$id.' class="tab button '.$active.'" style="width:'.$tabSize.'%;" test="'.$tabsCount.'" onclick="showTab(this, '.$count.');">
        '.$tab.'
        '.$activeArrow.'
      </div>

    ';

    $count++;
  }

  $out .= '
    </div>
  ';

  return $out;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// parst die Styledatei und ersetzt je nach gewähltem Theme die Farbeinstellungen
////////////////////////////////////////////////////////////////////////////////////////////////////////
function userStyle(){
    global $currentUser;
    global $interfaceData;

    if (file_exists("style.min.css")) {
        $style = file_get_contents("style.min.css");
    } 
    elseif (file_exists("style.css")) {
        $style = file_get_contents("style.css");
    } 
    else {
        die("App style file is missing!");
    }

    $themes = $interfaceData["themes"];
    $theme = $interfaceData["themes"][1];

    $theme_highlight = $themes[0];
    $theme_standard = $themes[1];

    foreach($themes as $key => $value){
        if($value["name"] == $currentUser['settings']['theme']){
            $theme = $themes[$key];
            break;
        }
    }

    unset($themes[0]);
    unset($themes[1]);

    if($theme["colors"]["highlight_active"] == ""){
        $theme["colors"]["highlight_active"] = $currentUser['settings']['highlight'];
    }

    $style_theme = str_replace($theme_standard["colors"], $theme["colors"], $style);

    return $style_theme;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// logoff
////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($_POST["cookie_delete"])){
    oauth_clear();
    setcookie('key_access', "", time() - 1, "/");
    setcookie('key_refresh', "", time() - 1, "/");
    die("true");
}