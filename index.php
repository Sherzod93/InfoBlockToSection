<?
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;   
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\ElementTable;

Loader::includeModule('iblock');

$fromIblockId = 49;


$sections = SectionTable::getList([
    'filter' => ['IBLOCK_ID' => $fromIblockId, '=DEPTH_LEVEL' => 1],
    'select' => ['ID', 'NAME','CODE'],
])->fetchAll();

$resultArray = [];

foreach ($sections as $section) {
    $resultArray[$section['ID']] = [
        'NAME' => $section['NAME'],
        'CODE' => $section['CODE'],
        'SUBSECTIONS' => getSubSectionsAndElements($section['ID'], $fromIblockId),
    ];
}

function getSubSectionsAndElements($parentId, $iblockId) {
    $result = [];

    $subSections = SectionTable::getList([
        'filter' => ['IBLOCK_ID' => $iblockId, '=IBLOCK_SECTION_ID' => $parentId],
        'select' => ['ID', 'NAME','CODE','DESCRIPTION','IBLOCK_SECTION_ID'],
    ])->fetchAll();

    foreach ($subSections as $subSection) {
        $result[$subSection['ID']] = [
            'NAME' => $subSection['NAME'],
            'CODE' => $subSection['CODE'],
            'IBLOCK_SECTION_ID' => $subSection['IBLOCK_SECTION_ID'],
            'ELEMENTS' => getSubSectionsAndElements($subSection['ID'], $iblockId),
        ];
    }
    
    
    $CIBlockElement = CIBlockElement::GetList(Array("SORT"=>"ASC"),
        Array('IBLOCK_ID' => $iblockId, '=IBLOCK_SECTION_ID' => $parentId),false, false,Array('ID', 'NAME','CODE','PREVIEW_TEXT','DETAIL_TEXT','PROPERTY_VENDOR',"IBLOCK_SECTION_ID"));
    
    while($element = $CIBlockElement->fetch()){
        $result[] = [
            'NAME' => $element['NAME'],
            'CODE' => $element['CODE'],
            'IBLOCK_SECTION_ID' => $element['IBLOCK_SECTION_ID'],
            'PREVIEW_TEXT' => $element['PREVIEW_TEXT'],
            'DETAIL_TEXT' => $element['DETAIL_TEXT'],           
            'VENDOR' => $element['PROPERTY_VENDOR_VALUE'],
        ];
    }

    
    return $result;
}



$sectionData = $resultArray;
$CIBlockSection = new CIBlockSection;
$CIBlockElement = new CIBlockElement;
$ToIblockID = 43;
foreach ($sectionData as $ID=>$section){
    $section["IBLOCK_ID"] = $ToIblockID;
    $section["IBLOCK_SECTION_ID"] = 730;
    $addedSections[$ID] = $CIBlockSection->Add($section);
}
foreach ($addedSections as $ID=>$addedSection){
    foreach ($sectionData[$ID]["SUBSECTIONS"] as $subID=>$subsection){
        $subsection["IBLOCK_ID"] = $ToIblockID;
        $subsection["IBLOCK_SECTION_ID"] = $addedSection;
        $arSubsectionElements[$subID] = $subsection["ELEMENTS"];
        $addedSubSections[$subID] = $CIBlockSection->Add($subsection);
    }
}
foreach ($addedSubSections as $subID=>$addedSubSection){
    foreach ($arSubsectionElements[$subID] as $element){
        $element["IBLOCK_ID"] = $ToIblockID;
        $element["IBLOCK_SECTION_ID"] = $addedSubSection;
        $element["PROPERTY_VALUES"] = ["VENDOR"=>$element["VENDOR"]];
        $arResult[] = $CIBlockElement->Add($element);
    }
}
