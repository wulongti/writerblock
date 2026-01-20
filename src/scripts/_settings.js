export const settings_name = "settings";
export const settings_function = ['$rootScope', '$scope', '$log', '$http', '$location', '$interval', function($rootScope, $scope, $log, $http, $location, $interval){
	$scope = $rootScope;

	$scope.debug("loading the Settings controller");
	// $scope.linky("settings");
	// $scope.setTab("main");
	if (!$scope.page) $scope.page = "settings";
	if (!$scope.tab) $scope.setTab("main");




}];