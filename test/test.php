<?php

$refFunc = new ReflectionFunction('mkdir');
foreach( $refFunc->getParameters() as $param ){

    printf("%s %s \n",
        $param->getName(),
        $param->getDefaultValue()
    );
}