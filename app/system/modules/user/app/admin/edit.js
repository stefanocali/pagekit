jQuery(function () {

    new Vue({

        el: '#js-user-edit',

        data: window.$data,

        ready: function () {

            this.$watch('user.status', function (status) {
                if (typeof status === 'string') {
                    this.user.status = parseInt(status);
                }
            });

        },

        methods: {

            save: function (e) {
                e.preventDefault();

                var roles = this.roles.filter(function (role) { return role.selected; }).map(function (role) { return role.id; });

                this.$resource('api/user/:id').save({ id: this.user.id }, { user: this.user, password: this.password, roles: roles }, function (data) {

                    if (data.user) {
                        this.$set('user', data.user);
                    }

                    UIkit.notify(data.message);

                }, function (data) {

                    UIkit.notify(data, 'danger');

                });
            }

        }

    });

});
