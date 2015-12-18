<?php

namespace Jig;

interface Escaper
{
    public function escapeHTML($string);
    public function escapeHTMLAttribute($string);
    public function escapeJavascript($string);
    public function escapeCSS($string);
    public function escapeURLComponent($string);
}
