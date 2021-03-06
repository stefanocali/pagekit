<?php $view->script('settings', 'site:app/settings.js', 'vue') ?>

<div id="settings" class="uk-form uk-form-horizontal">

    <div class="uk-margin uk-flex uk-flex-space-between uk-flex-wrap" data-uk-margin>
        <div data-uk-margin>

            <h2 class="uk-margin-remove">{{ 'Settings' | trans }}</h2>

        </div>
        <div data-uk-margin>

            <button class="uk-button uk-button-primary" v-on="click: save">{{ 'Save' | trans }}</button>

        </div>
    </div>

    <div class="uk-form-row">
        <label for="form-title" class="uk-form-label">{{ 'Title' | trans }}</label>
        <div class="uk-form-controls">
            <input id="form-title" class="uk-form-width-large" type="text" v-model="config.site.title">
        </div>
    </div>

    <div class="uk-form-row">
        <label for="form-description" class="uk-form-label">{{ 'Description' | trans }}</label>
        <div class="uk-form-controls">
            <textarea id="form-description" class="uk-form-width-large" rows="5" v-model="config.site.description"></textarea>
        </div>
    </div>

    <div class="uk-form-row">
        <label for="form-offlinemessage" class="uk-form-label">{{ 'Offline Message' | trans }}</label>
        <div class="uk-form-controls">
            <textarea id="form-offlinemessage" class="uk-form-width-large" placeholder="{{ &quot;We'll be back soon.&quot; | trans }}" rows="5" v-model="config.maintenance.msg"></textarea>
            <p class="uk-form-controls-condensed">
                <label><input type="checkbox" value="1" v-model="config.maintenance.enabled"> {{ 'Put the site offline and show the offline message.' | trans }}</label>
            </p>
        </div>
    </div>

</div>
