<?php

namespace JigDemo\Controller;

class Index {

    function display()
    {
        return getTemplateCallable('index');
    }
}

 