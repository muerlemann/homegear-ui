////////////////////////////////////////////////////////////////////////////////////////////////////////
//
////////////////////////////////////////////////////////////////////////////////////////////////////////
let socket_switch_l2 = clone(shif_device);
socket_switch_l2.methods.change = function(event) {
    homegear.value_set_clickcounter(this, this.output, !this.props.value);
}
socket_switch_l2.template = `
    <shif-generic-l2 v-bind:icon="cond.icon.name"
                     v-bind:title="dev.label"
                     v-bind:active="{icon: cond.icon.color, text: cond.text.color}"
                     v-bind:status="status"
                     v-bind:place="place"
                     v-bind:actions="true"
                     v-on:click_icon="change"
                     v-on:click="level3(device, breadcrumb)">
    </shif-generic-l2>
`;

let socket_switch_l3 = clone(shif_device);
socket_switch_l3.methods.change = function(event) {
    homegear.value_set_clickcounter(this, this.output, !this.props.value);
}
socket_switch_l3.template = `
    <shif-generic-l2 v-bind:icon="cond.icon.name"
                     v-bind:title="title"
                     v-bind:active="{icon: cond.icon.color, text: cond.text.color}"
                     v-bind:place="place"
                     v-bind:status="status_minimal()"
                     v-on:click="change">
    </shif-generic-l2>
`;

shif_comps_create('socketSwitch', socket_switch_l2, socket_switch_l3);



let socket_button_l2 = clone(shif_device);
socket_button_l2.template = `
    <shif-generic-l2 v-bind:icon="cond.icon.name"
                     v-bind:title="dev.label"
                     v-bind:active="{icon: cond.icon.color, text: cond.text.color}"
                     v-bind:status="status"
                     v-bind:place="place"
                     v-on:click="level3(device, breadcrumb)">
    </shif-generic-l2>
`;

let socket_button_l3 = clone(shif_device);
socket_button_l3.methods.change = function(event, down) {
    homegear.value_set_clickcounter(this, this.output, down);
}
socket_button_l3.template = `
    <shif-generic-l2 v-bind:icon="cond.icon.name"
                     v-bind:title="title"
                     v-bind:active="{icon: cond.icon.color, text: cond.text.color}"
                     v-bind:status="status_minimal()"
                     v-bind:place="place"
                     v-on:mousedown="change($event, true)"
                     v-on:mouseup="change($event, false)">
    </shif-generic-l2>
`;

shif_comps_create('socketButton', socket_button_l2, socket_button_l3);
