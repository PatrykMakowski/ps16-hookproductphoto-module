{*
	Smarty Template
	@name: HookProductPhoto
	@hook: AdminProductsExtra
	@author: Patryk Makowski
*}
<div id="product-hookproductphoto" class="panel product-tab">

	<h4 class="tab">{l s='Zdjęcie w parametrach' mod='hookproductphoto'}</h4>

	<p>
		{l s='Wybierz zdjęcie, które pokaże się w zakładce parametrów produktu' mod='hookproductphoto'}
	</p>
	
	{include file="controllers/products/multishop/check_fields.tpl" product_tab="hookproductphoto"}
	
	<hr />
	
	<div class="form-group text-center" id="hpp_image">
		{if isset($images) && $images}
			{assign var=imgselected value=0}
			{foreach from=$images item=image}
			<div class="col-lg-2 text-center">
				<input type="radio" name="hpp_image" id="hpp_image_{$image.id_image}" value="{$image.id_image}" {strip} 
				{if $image.id_image == $current_hpp && $current_hpp}
					{assign var=imgselected value=1}
					checked
				{/if}{/strip} />
				<br>
				<label class="control-label" for="hpp_image_{$image.id_image}"><img src="{$link->getImageLink('_', $image.id_image, 'small_default')}" alt=" " /></label>
			</div>
			{/foreach}
			
			<div class="col-lg-1 text-center">
				<input type="radio" name="hpp_image" id="hpp_image_null" value="null" {if $imgselected == 0 || !$current_hpp}checked{/if} />
				<br>
				<label class="control-label" for="hpp_image_null">
					<span class="label-tooltip" data-toggle="tooltip" title="{l s='Jeśli wybierzesz tę opcję w parametrach nie będzie zdjęcia' mod='hookproductphoto'}">
					{l s='Bez zdjęcia' mod='hookproductphoto'}
					</span>
				</label>
			</div>
			
		{else}
			<input type="hidden" name="hpp_image" value="null" />
			{l s='Aby wybrać zdjęcie do bloku należy najpierw je dodać!' mod='hookproductphoto'}
		{/if}			
	</div>
	
	<hr />
	
	<div class="panel-footer">
		<a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay'}</button>
	</div>
</div>