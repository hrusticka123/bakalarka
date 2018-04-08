<?php
$str = "<vS7UnhaA1F15vFr9R8eBV6ymQlMxrsPuAjAcWDyWno@hruska.blesmrt.cf>
<CANuw_od16Gs+WFUniPUJkmDeWV1esqmQq1dhaayBvUwP7fjYmQ@mail.gmail.com> <SkqJAMuBpRXRssbMCwq2UWL0cabgCoyc7QLbjxeWng@hruska.blesmrt.cf>
";
var_dump(purerefs($str));
function purerefs($references)
{
    $references = htmlspecialchars($references);
    $references = str_replace("&gt;", "",$references);
    $parts = explode("&lt;",$references);
    array_splice($parts,0,1);
    $newrefs = array();
    foreach ($parts as $part)
    {
        $newrefs[] = preg_replace('/\s+/', '', $part);
    }
    return $newrefs;

}

?>