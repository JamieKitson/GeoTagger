<?php

static $regions = array(
    'Africa' => DateTimeZone::AFRICA,
    'America' => DateTimeZone::AMERICA,
    'Antarctica' => DateTimeZone::ANTARCTICA,
    'Arctic' => DateTimeZone::ARCTIC,
    'Asia' => DateTimeZone::ASIA,
    'Atlantic' => DateTimeZone::ATLANTIC,
    'Australia' => DateTimeZone::AUSTRALIA,
    'Europe' => DateTimeZone::EUROPE,
    'Indian' => DateTimeZone::INDIAN,
    'Pacific' => DateTimeZone::PACIFIC
);

foreach ($regions as $name => $mask) 
{
    $s = "";
    foreach( DateTimeZone::listIdentifiers($mask) as $bah)
    {
      $sub = substr($bah, strpos($bah, '/') + 1);
        $s .= '<option value="'.$sub.'">'.str_replace("_", " ", $sub)."</option>\n";
    }
    file_put_contents($name.'.html', $s);
}

?>


