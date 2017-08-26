<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';
\Bitrix\Main\Loader::includeModule("aero.generator");

// @TODO leave step, counters etc only in Steps
// JsonBar shuld be responsible only for notify and calc percent

$steps = new \Aero\Generator\Steps();
if(\Aero\Generator\JsonBar::isAjax()){
    $progress = new \Aero\Generator\JsonBar(
        $steps->getTotal(),
        $steps->getCurrent()
    );
    while($stepsCompleted = $steps->createNext())
        $progress->advance($stepsCompleted);
    $progress->finish();
}

if(\Aero\Generator\JsonBar::isAjax()){
    // Get total and cur from step
    // Calc percantage
    // Send respnse
}


$APPLICATION->SetAdditionalCSS('/bitrix/panel/aero.generator/aero_generator.css');
\CJSCore::Init(array("aero_generator"));

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';
?>
<div id="progressbar-container">
    <form data-step="<?=$steps->getCurrent();?>"  id="progress-starter" enctype="multipart/form-data" method="post" action="aero_generator_controller.php">
        <input type="submit" value="Upload!" />
    </form>
    <div id="progressbar">
        <div class="pg-progressbar">
            <div class="pg-progress" id="pg-percent">
                <div class="pg-progressstyle"></div>
                <div class="pg-invertedtext" id="pg-text-1"></div>
            </div>
            <div class="pg-text" id="pg-text-2"></div>
        </div>
    </div>
    <div id="progressBar">
        <div id="progressDone"></div>
    </div>
</div>
<?
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';