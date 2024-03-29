<?php
/* @var $Assets \Rdb\Modules\RdbAdmin\Libraries\Assets */
/* @var $Modules \Rdb\System\Modules */
/* @var $Views \Rdb\System\Views */
/* @var $Url \Rdb\System\Libraries\Url */
?>
                        <h1 class="rdba-page-content-header"><?php echo __('Previous emails'); ?></h1>
                        <p><?php echo sprintf(__('Previously used emails of the user %s.'), '<a href="' . ($editUserUrl ?? '') . '"><strong id="user_login"></strong></a>') ?></p>

                        <div class="form-result-placeholder"></div>

                        <div class="rd-datatable-wrapper">
                            <table id="list-email-changed-history-table" class="rd-datatable responsive">
                                <thead>
                                    <tr>
                                        <th class="column-primary"><?php echo __('Email'); ?></th>
                                        <th><?php echo __('Changed date'); ?></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr>
                                        <th class="column-primary"><?php echo __('Email'); ?></th>
                                        <th><?php echo __('Changed date'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div><!--.rd-datatable-wrapper-->

                        <template id="list-email-changed-history-table-row-template">
                            {{#each field_value}}
                            <tr>
                                <td class="column-primary" data-colname="<?php echo esc__('Email'); ?>">
                                    {{this.email}}
                                    <button class="toggle-row" type="button">
                                        <i class="faicon fa-solid fa-caret-down fa-fw fontawesome-icon" data-toggle-icon="fa-caret-down fa-caret-up"></i>
                                        <span class="screen-reader-only"><?php echo __('Show more details'); ?></span>
                                    </button>
                                </td>
                                <td data-colname="<?php echo esc__('Changed date'); ?>">{{#formatDate this.gmtdate}}{{/formatDate}}</td>
                            </tr>
                            {{/each}}
                        </template>