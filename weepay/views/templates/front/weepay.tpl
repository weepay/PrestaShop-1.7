{*
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    weepay <info@weepay.com>
*  @copyright 2019 weepay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of weepay
*}
<div class= "row"> 
    <div class="col-xs-12">
        {if (isset($error)) }
        <div class="paiement_block">
            <p class="alert alert-warning">{$error}</p>
        </div>
        {/if}

<div id="loadingWeePay">
    <div class="sk-cube-grid"><div class="sk-cube sk-cube1"></div><div class="sk-cube sk-cube2"></div><div class="sk-cube sk-cube3"></div><div class="sk-cube sk-cube4"></div><div class="sk-cube sk-cube5"></div> <div class="sk-cube sk-cube6"></div><div class="sk-cube sk-cube7"></div><div class="sk-cube sk-cube8"></div><div class="sk-cube sk-cube9"></div></div>
    <div class="weelogo">
        <p><span>wee</span>pay</p>

    </div>
</div>
        <div id="weePay-checkout-form" class="{$form_class}" style="display:none;">
          {$response nofilter}
        </div>
        <div class="weepayImage" id="weeImage">
          <img src="{$cards}" class="form-class" />
        <p id="termsError" style='color:red;'>{$contract_text}</p>
        
        </div>
    </div>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
{literal}
<style>
.sk-cube8,.sk-cube7, .sk-cube4 {background-color:#12bde2!important;}.sk-cube-grid {   width: 40px;   height: 40px;   margin: 100px auto; }.sk-cube-grid .sk-cube {   width: 33%;   height: 33%;   background-color: #1f216e;   float: left;   -webkit-animation: sk-cubeGridScaleDelay 1.3s infinite ease-in-out;           animation: sk-cubeGridScaleDelay 1.3s infinite ease-in-out; }.sk-cube-grid .sk-cube1 {   -webkit-animation-delay: 0.2s;           animation-delay: 0.2s; } .sk-cube-grid .sk-cube2 {   -webkit-animation-delay: 0.3s;           animation-delay: 0.3s; } .sk-cube-grid .sk-cube3 {   -webkit-animation-delay: 0.4s;           animation-delay: 0.4s; } .sk-cube-grid .sk-cube4 {   -webkit-animation-delay: 0.1s;           animation-delay: 0.1s; } .sk-cube-grid .sk-cube5 {   -webkit-animation-delay: 0.2s;           animation-delay: 0.2s; } .sk-cube-grid .sk-cube6 {   -webkit-animation-delay: 0.3s;           animation-delay: 0.3s; } .sk-cube-grid .sk-cube7 {   -webkit-animation-delay: 0s;           animation-delay: 0s; } .sk-cube-grid .sk-cube8 {   -webkit-animation-delay: 0.1s;           animation-delay: 0.1s; } .sk-cube-grid .sk-cube9 {   -webkit-animation-delay: 0.2s;           animation-delay: 0.2s; }  @-webkit-keyframes sk-cubeGridScaleDelay {   0%, 70%, 100% {     -webkit-transform: scale3D(1, 1, 1);             transform: scale3D(1, 1, 1);   } 35% {     -webkit-transform: scale3D(0, 0, 1);             transform: scale3D(0, 0, 1);   } }  @keyframes sk-cubeGridScaleDelay {   0%, 70%, 100% {     -webkit-transform: scale3D(1, 1, 1);             transform: scale3D(1, 1, 1);   } 35% {     -webkit-transform: scale3D(0, 0, 1);      transform: scale3D(0, 0, 1);   } }.weelogo p{color:#1f216e;text-align:center;margin-top:-100px}.weelogo p span{color:#12bde2;}
.weepayImage {
    width: 100%;
    text-align: center;
    margin-bottom: 30px;
    margin-top: 30px;
}

.weepayImage img {
  width: 500px;
  margin-bottom: 15px;
  text-align: center;
}

.weepayImage p {
  text-align:center;
  font-weight: bold;
}
</style>
<script>

var contractCheck = document.getElementsByClassName("js-terms");

$( document ).ready(function() {

  if(contractCheck.length == 1) {

        $("input[name='payment-option']").click(function () {
            $("button[class='btn btn-primary center-block']").show();

            if ($("input[id='conditions_to_approve[terms-and-conditions]']").is(':checked')) {

                $("#loadingWeePay").hide();
                $("#weePay-checkout-form").show();
                $('#weeImage').hide();
           
            } else {

              $('#weeImage').show();
              $("#loadingWeePay").show();
              $("#weePay-checkout-form").hide();                 
            
            }

        });

        $("input[data-module-name='weepay']").click(function () {
              
                $("button[class='btn btn-primary center-block']").hide();

                $("input[id='conditions_to_approve[terms-and-conditions]']").change(function () {

                    if (this.checked) {
            

                         $("#loadingWeePay").hide();
                         $("#weePay-checkout-form").show();
                         $('#weeImage').hide();
                     
            
                    } else {

                       $('#weeImage').show();
                       $("#loadingWeePay").show();
                       $("#weePay-checkout-form").hide();
                       
                    }
              });
      });        
  } else {

        $("input[name='payment-option']").click(function () {
            $("button[class='btn btn-primary center-block']").show();
        });

        $("input[data-module-name='weepay']").click(function () {
              
          $("button[class='btn btn-primary center-block']").hide();

          $("#loadingWeePay").hide();
          $('#weeImage').hide();
          $("#weePay-checkout-form").show();

        });        
  }

  $(".material-icons").click(function(){

      location.reload(true);

  });

  $("#promo-code > form ").submit(function(){

    var promoStatus = document.getElementsByClassName("promo-input");
    var promoValue = promoStatus[0].value.length;

    if(promoValue != 0) {
        
      location.reload(true);

    }

  });


  

});


</script>
{/literal}