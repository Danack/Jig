



{syntaxHighlighter lang='php'}
const ✓ = true;
const ✕ = false;

function ≠($left, $right) {
return $left != $right;
}

function ≅($left, $right) {
return ($left > $right - 0.0001) && ($left < $right + 0.0001);
}
