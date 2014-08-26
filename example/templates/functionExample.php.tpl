{include file='pageStart'}

<div class="container">



<h4>Global functions can be bound</h4>

<p>
{globalFunction()}
</p>


<h4>Closure function</h4>

<p>

{closureFunction(1, 2, 3)}

</p>



<h4>Class function</h4>

<p>
    {classFunction()}
</p>



<h4>View Model functions</h4>

<p>
    Any public function of the ViewModel can be called by the template without that function being bound.
    <br/>

    The company motto is '{showCompanyMotto()}'

</p>


</div>

{include file='pageEnd'}