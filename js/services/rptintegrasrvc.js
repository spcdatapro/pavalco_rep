(function(){

    var rptintegrasrvc = angular.module('pavalco.rptintegrasrvc', []);

    rptintegrasrvc.factory('rptIntegraSrvc', ['commonSrvc', function(commonSrvc){
        var urlBase = 'rptintegra.php';

        return {
            rptIntegracionML: function(obj){
                return commonSrvc.doGET(urlBase + '/rptintegra/' + obj.anio + '/' + obj.dmes + '/' + obj.ames + '/' + obj.topten);
            }
        };
    }]);

}());