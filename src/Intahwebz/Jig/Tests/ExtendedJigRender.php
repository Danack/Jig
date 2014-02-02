<?php


namespace Intahwebz\Jig\Tests;


use Intahwebz\Jig\JigConfig;



class ExtendedJigRender extends \Intahwebz\Jig\JigRender  {

    function __construct(JigConfig $jigConfig, \Auryn\Provider $provider) {

        parent::__construct($jigConfig, $provider);

        $this->bindProcessedBlock(
            'spoiler',
            [$this, 'spoilerBlockEnd'],
            [$this, 'spoilerBlockStart']
        );
    }


    
    function spoilerBlockStart(){
        $spoiler = "<div>";
        $spoiler .= "<span class='clickyButton' onclick='showHide(this, \"spoilerHidden\");'>Spoiler</span>";
        $spoiler .= "<div class='spoilerBlock' style=''>";
        $spoiler .= "<div class='spoilerHidden' style='display: none;'>";

        return $spoiler;
    }

    /**
     *
     */
    function spoilerBlockEnd($content){
        $spoiler = $content;
        $spoiler .= "<div style='clear: both;'></div>";
        $spoiler .= "</div>";
        $spoiler .="</div></div>";
        return $spoiler;
    }
    
}

 