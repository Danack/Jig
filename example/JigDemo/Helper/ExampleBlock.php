<?php


namespace JigDemo\Helper;


class ExampleBlock
{
    function warningBlockStart()
    {
        $output = "<div class='warning'>";
        $output .= "<span class='warningTitle'>* Warning *</span>";
        echo $output;
    }

    function warningBlockEnd($content)
    {
        $output = $content;
        $output .= "</div>";
        echo $output;
    }
}
