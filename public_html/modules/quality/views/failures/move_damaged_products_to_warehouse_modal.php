<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="products_to_locationModal" tabindex="-1" role="dialog">
  <div class="modal-dialog  modal-xl">
      <div class="modal-content">
          <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <h4 class="modal-title">
                  <span class="add-title"></span>
              </h4>
          </div>
          <div class="modal-body">
              <div class="row">

                 <div class="col-md-12">
                   <div class="table-responsive">
                    <table class="table items damaged_products no-mtop">
                      <thead>
                        <tr>
                          <th><?php echo _l('commodity_code'); ?></th>
                          <th><?php echo _l('commodity_name'); ?></th>
                          <th><?php echo _l('warehouse_id'); ?></th>
                          <th><?php echo _l('quantities'); ?></th>
                          <th><?php echo _l('unit_price'); ?></th>
                          <th><?php echo _l('quality_quantity_of_damaged_products'); ?></th>
                          <th><?php echo _l('quality_serial_number'); ?></th>
                          <th><?php echo _l('quality_faulty_goods_warehouse'); ?></th>
                          <th><?php echo _l('quality_select'); ?></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if(count($products) > 0){ ?>
                          <?php foreach ($products as $key => $product) {?>
                            <?php 
                            $arr_serial_numbers = [];
                            $serial_numbers = (!is_null($product['serial_number']) && $product['serial_number'] != '') ? explode(',', $product['serial_number']) : [] ;
                            if(count($serial_numbers) > 0){
                              foreach ($serial_numbers as $key => $value) {
                                $arr_serial_numbers[] = [
                                  'value' => $value,
                                ];
                              }
                            }
                            $warehouse_name = '';
                            $unit_name = '';
                            if(quality_get_status_modules('warehouse')){
                              $warehouse_name = get_warehouse_name($product['warehouse_id'], true);
                              $unit_name = wh_get_unit_name($product['unit_id']);
                            }
                            ?>
                            
                            <tr>
                              <td><?php echo html_entity_decode($product['commodity_code']); ?></td>
                              <td><?php echo html_entity_decode($product['commodity_name']); ?></td>
                              <td><?php echo html_entity_decode($warehouse_name); ?></td>
                              <td><?php echo html_entity_decode($product['quantities'].' '.$unit_name); ?></td>
                              <td><?php echo html_entity_decode($product['unit_price']); ?></td>
                              <td>
                                <div class="form-group" app-field-wrapper="quantity_of_damaged"><input type="number" name="quantity_of_damaged" class="form-control" step="any" min="0" max="<?php echo html_entity_decode($product['quantities']); ?>" value="0"></div>
                              </td>
                              <td>

                                <div class="form-group">
                                  <select name="serial_number[]" class="selectpicker" multiple="true"  data-live-search="true" data-width="100%" >
                                    <?php foreach($arr_serial_numbers as $serial_number) { ?>
                                      <option value="<?php echo new_html_entity_decode($serial_number['value']); ?>"><?php echo new_html_entity_decode($serial_number['value']); ?></option>
                                    <?php } ?>
                                  </select>
                                </div>

                              </td>
                              <td>

                                <div class="form-group">
                                  <select name="to_stock_name" class="selectpicker" data-live-search="true" data-width="100%" >
                                    <?php foreach($faulty_goods_warehouse as $faulty_goods_warehous) { ?>
                                      <option value="<?php echo new_html_entity_decode($faulty_goods_warehous['id']); ?>"><?php echo new_html_entity_decode($faulty_goods_warehous['name']); ?></option>
                                    <?php } ?>
                                  </select>
                                </div>

                              </td>
                              <td>
                                <div class="checkbox checkbox-primary checkbox-inline"><input type="checkbox" name="search_item_id" value="<?php echo html_entity_decode($product['commodity_code']); ?>" id="<?php echo html_entity_decode($product['commodity_code']); ?>"><label for="<?php echo html_entity_decode($product['commodity_code']); ?>"></label></div>
                                <div class="article_search_result">
                                  <input type="hidden" name="commodity_code" value="<?php echo new_html_entity_decode($product['commodity_code']); ?>">
                                  <input type="hidden" name="commodity_name" value="<?php echo new_html_entity_decode($product['commodity_name']); ?>">
                                  <input type="hidden" name="unit_id" value="<?php echo new_html_entity_decode($product['unit_id']); ?>">
                                  <input type="hidden" name="warehouse_id" value="<?php echo new_html_entity_decode($product['warehouse_id']); ?>">
                                  <input type="hidden" name="quantities" value="<?php echo new_html_entity_decode($product['quantities']); ?>">
                                  <input type="hidden" name="unit_price" value="<?php echo new_html_entity_decode($product['unit_price']); ?>">
                                  <input type="hidden" name="expiry_date" value="<?php echo new_html_entity_decode($product['expiry_date']); ?>">
                                  <input type="hidden" name="lot_number" value="<?php echo new_html_entity_decode($product['lot_number']); ?>">
                                </div>
                              </td>
                            </tr>
                          <?php } ?>
                        <?php } ?>
                      </tbody>
                    </table>

                  </div>
              </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="btn_select(); return false;"><?php echo _l('submit'); ?></button>
    </div>
</div>
</div>
</div>
<?php echo form_hidden('name_item_id'); ?>
<?php echo form_hidden('name_description'); ?>
<?php echo form_hidden('name_rate'); ?>
<?php echo form_hidden('name_category_id'); ?>
<?php echo form_hidden('first_item_id'); ?>
<?php require 'modules/quality/assets/js/failures/failure_location_js.php';  ?>

