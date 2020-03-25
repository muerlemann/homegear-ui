Vue.component('shif-house-floors-rooms', {
    mixins: [mixin_print_mounted()],

    computed: {
        has_multiple_floors: function () {
            return Object.keys(interfaceData.floors).length > 1;
        }
    },

    data: function () {
        return {
            maxWidth: '1080px',
        };
    },

    template: `
        <div id="house_rooms" v-bind:style="{'max-width': maxWidth}">
            <div v-for="floor_val, floor_key in interfaceData.floors">
                <div v-if="has_multiple_floors"
                     class="roomSelectTitle">
                    {{ floor_val.name }}
                </div>

                <div class="rooms_wrapper">
                    <shif-room v-for="room_val in floor_val.rooms"
                               v-bind:key="room_val"
                               v-bind:floor="{key: floor_key, value: floor_val}"
                               v-bind:room="room_val" />
                </div>
            </div>
        </div>
    `,
});



Vue.component('shif-house-collected-entries', {
    mixins: [mixin_components, mixin_print_mounted()],

    props: {
        layer: {
            type: Number,
            required: true,
        },
    },

    computed: {
        dev_objs: function () {
            if (this.layer === 2) {
                return interfaceData.rooms[this.$route.params.room]
                                    .devices
                                    .map(dev => this.find_component(interfaceData.devices[dev], 'l2'));
            }

            if (this.layer === 3) {
                const device = interfaceData.devices[this.$route.params.device];
                return this.find_component(device, 'l3');
            }

            throw 'Not implemented';
        },
    },

    template: `
        <div>
            <template v-for="dev in dev_objs">
                <component v-bind="dev" v-bind:include_place="false">
                </component>

                <template v-if="debug">
                    {{ dev | pretty | log }}
                </template>
            </template>
        </div>
    `,
});



let ShifHouseLvl3 = {
    mixins: [mixin_print_mounted('shif-house-lvl3')],

    template: `
        <shif-house-collected-entries v-bind:layer="3" />
    `
};



let ShifHouseLvl2 = {
    mixins: [mixin_print_mounted('shif-house-lvl2')],

    computed: {
        breadcrumb_name: function () {
            const floor_name = interfaceData.options.showFloor === true
                                ? interfaceData.floors[this.$route.params.floor].name + ' - '
                                : '';

            return floor_name + interfaceData.rooms[this.$route.params.room].name
        },
    },

    mounted: function () {
        interfaceData.i18n[this.$route.name] = this.breadcrumb_name;
    },

    template: `
        <shif-house-collected-entries v-bind:layer="2" />
    `
};



let ShifHouseRooms = {
    mixins: [mixin_print_mounted('shif-house-rooms')],
    name: 'ShifHouseLvl1',

    template: `
        <shif-mainmenu-tabs>
            <shif-house-floors-rooms />
        </shif-mainmenu-tabs>
    `
};



let ShifAllDevicesLvl3 = Vue.component('shif-all-devices-level-3', {
    mixins: [mixin_print_mounted()],

    template: `
        <shif-house-collected-entries v-bind:layer="3" />
    `
});



let ShifHouseDevices = {
    mixins: [mixin_print_mounted('shif-house-devices')],

    template: `
        <shif-mainmenu-tabs>
            <shif-all-devices />
        </shif-mainmenu-tabs>
    `
};



let ShifHouse = {
    mixins: [mixin_print_mounted('shif-house')],

    template: `
        <shif-paging id="house" />
    `,
};
