

Function test

{helper type='Jig\PlaceHolder\PlaceHolderHelper'}

{testFunction1()}


{testFunction2()}


{testFunction3()}

{if isAllowed('admin', 'edit')}
    isAllowed was true
{/if}