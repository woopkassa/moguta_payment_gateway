<!--
Доступны переменные:
  $pluginName - название плагина
  $lang - массив фраз для выбранной локали движка
  $options - набор данного плагина хранимый в записи таблиц mg_setting  
-->
<div class="section-<?php echo $pluginName ?>">
    <!-- $pluginName - задает название секции для разграничения JS скрипта -->
    <!-- Тут начинается верстка видимой части станицы настроек плагина-->
    <div class="widget-table-body">
        <div class="wrapper-entity-setting">
            <!-- Тут начинается  Верстка базовых настроек  плагина (опций из таблицы  setting)-->
            <div class="widget-table-action base-settings">
                <ul class="list-option"><!-- список опций из таблицы setting-->
                    <li class="section"><?php echo $lang['SECTION_SERVICE_SETTINGS']; ?></li>
                    <li>
                        <label>
                            <span class="custom-text" style="float:left; width:209px;"><?php echo $lang['PP_URL_API']?>:</span>
                            <input type="text" name="api_key" value="<?php echo $options['url_api'] ?>"
                                   class="tool-tip-right" title="<?php echo $lang['T_TIP_PP_URL_API'] ?>" style = "width: 200px;"/>
                        </label>
                    </li>
                    <li>
                        <label>
                            <span class="custom-text" style="float:left; width:209px;"><?php echo $lang['PP_LOGIN_API']?>:</span>
                            <input type="text" name="api_key" value="<?php echo $options['login_api'] ?>"
                                   class="tool-tip-right" title="<?php echo $lang['T_TIP_PP_LOGIN_API'] ?>" style = "width: 200px;"/>
                        </label>
                    </li>
                    <li>
                        <label>
                            <span class="custom-text" style="float:left; width:209px;"><?php echo $lang['PP_PASSWORD_API']?>:</span>
                            <input type="text" name="api_key" value="<?php echo $options['password_api'] ?>"
                                   class="tool-tip-right" title="<?php echo $lang['T_TIP_PP_PASSWORD_API'] ?>" style = "width: 200px;"/>
                        </label>
                    </li>
                    <li>
                        <label>
                            <span class="custom-text" style="float:left; width:209px;"><?php echo $lang['PP_ORDER_PREFIX']?>:</span>
                            <input type="text" name="api_key" value="<?php echo $options['order_prefix'] ?>"
                                   class="tool-tip-right" title="<?php echo $lang['T_TIP_PP_ORDER_PREFIX'] ?>" style = "width: 200px;"/>
                        </label>
                    </li>
                    <li>
                        <label>
                            <span class="custom-text" style="float:left; width:209px;"><?php echo $lang['PP_SERVICE_NAME']?>:</span>
                            <input type="text" name="api_key" value="<?php echo $options['service_name'] ?>"
                                   class="tool-tip-right" title="<?php echo $lang['T_TIP_PP_SERVICE_NAME'] ?>" style = "width: 200px;"/>
                        </label>
                    </li>
                </ul>
                <div class="link-fail">Все поля настроек являются обязательными для заполнения!</div>
                <div class="clear"></div>
                <button class="tool-tip-bottom base-setting-save save-button custom-btn success button" data-id=""
                        title="<?php echo $lang['T_TIP_SAVE'] ?>" style="margin:15px;">
                    <i class="fa fa-floppy-o"></i> <span><?php echo $lang['SAVE'] ?></span> <!-- кнопка применения настроек -->
                </button>
                <div class="clear"></div>
            </div>
            <div class="clear"></div>
        </div>
    </div>
</div>