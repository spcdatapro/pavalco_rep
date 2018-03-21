(function(){

    var rptintegractrl = angular.module('pavalco.rptintegractrl', []);

    rptintegractrl.controller('rptIntegraCtrl', ['$scope', 'rptIntegraSrvc', function($scope, rptIntegraSrvc){
        $scope.meses = [
            {id:  1, 'mes': 'Enero'}, {id:  2, 'mes': 'Febrero'}, {id:  3, 'mes': 'Marzo'}, {id:  4, 'mes': 'Abril'}, {id:  5, 'mes': 'Mayo'}, {id:  6, 'mes': 'Junio'},
            {id:  7, 'mes': 'Julio'}, {id:  8, 'mes': 'Agosto'}, {id:  9, 'mes': 'Septiembre'}, {id: 10, 'mes': 'Octubre'}, {id: 11, 'mes': 'Noviembre'}, {id: 12, 'mes': 'Diciembre'}
        ];
        $scope.param = {anio: moment().year(), dmes: (moment().startOf('year').month() + 1).toString(), ames: (moment().month() + 1).toString(), topten: 1};
        //$scope.param = {anio: 2016, dmes: '1', ames: '2', topten: 1};
        $scope.data = null;
        $scope.showColumn = {1: false, 2: false, 3: false, 4: false, 5: false, 6: false, 7: false, 8: false, 9: false, 10: false, 11: false, 12: false};
        $scope.cargando = false;


        $scope.generar = function(){
            //console.log($scope.param);
            for(var i = 1; i <= 12; i++){
                $scope.showColumn[i] = ((i >= parseInt($scope.param.dmes)) && (i <= parseInt($scope.param.ames)));
            }
            //console.log($scope.showColumn);
            $scope.cargando = true;
            $scope.param.topten = $scope.param.topten !== null && $scope.param.topten !== undefined ? $scope.param.topten : 0; 
            rptIntegraSrvc.rptIntegracionML($scope.param).then(function(d){
                $scope.data = d;
                //console.log($scope.data);
                $scope.cargando = false;
            });
        };

        $scope.toXLS = function(){
            var blob = new Blob([document.getElementById('toXLS').innerHTML], {
                //type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=windows-1252"
            });
            saveAs(blob, "Reporte.xls");
        };

    }]);

}());