(function(){

    var comun = angular.module('pavalco.commonsrvc', []);

    comun.factory('commonSrvc', ['$http', function($http){
        return {
            doGET: function(urlBase){
                return $http({method: 'GET', url: urlBase}).then(function(response){
                    return response.data;
                });
            },
            doPOST: function(urlBase, obj){
                return $http({
                    url: urlBase,
                    method: 'POST',
                    data: obj
                }).then(function(response){
                    return response.data;
                });
            }
        };
    }]);

}());