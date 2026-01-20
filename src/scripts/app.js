/* main JS file entrypoint for parcel to process */
require('angular');
require('mn-touch');

const currenttime = new Date();
const copyyear = currenttime.getFullYear();

window.debugOutput = true;

// Define the `app` module
const wBlock = angular.module('wBlock', [require('angular-route'), require('angular-sanitize'), 'mn']);

// Set the routing config
wBlock.config(['$routeProvider', '$locationProvider',
	function ($routeProvider, $locationProvider) {
		$routeProvider
			.when("/welcome", {
				templateUrl: '/templates/welcome.html',
				controller: 'welcome'
			})
			.when("/changelog", {
				templateUrl: '/templates/changelog.html'
			})
			.when("/books", {
				templateUrl: '/templates/books.html',
				controller: 'books'
			})
			.when("/tos", {
				templateUrl: '/templates/tos.html'
			})
			.when("/settings", {
				templateUrl: '/templates/settings.html',
				controller: 'settings'
			})
			.otherwise({ 
				redirectTo: "/welcome"
			});
		//$locationProvider.html5Mode(true);
	}
]);

wBlock.controller('mainCtrl', ['$rootScope', '$scope', '$log', '$http', '$location', '$interval', function($rootScope, $scope, $log, $http, $location, $interval){
	$scope = $rootScope;
	//set helper debugging functions
	$scope.debug = function(message){
		if (window.debugOutput){
			console.log(message);
		}
	}
	window.logScope = function(){
		//$scope.debug($scope);
		console.log($scope);
	}

	//get config vars from outside the controller into scope
	$scope.parseInt = parseInt;
	$scope.copyyear = copyyear;
	$scope.nocache = new Date().getTime();

	//vars that need to go to other controllers
	$scope.page = "";
	$scope.tab = "";
	$scope.loginform = {};
	$scope.accountform = {};
	$scope.user = {};
	$scope.books = {};
	$scope.book = {};
	$scope.error = {};
	$scope.time = {};
	$scope.loading = false;

	//check if user has localStorage data
	if (window.localStorage.getItem("user")) {
		//if localStorage data, populate the scope
		$scope.user = JSON.parse(window.localStorage.getItem('user'));
	}
	//do the same for the book data
	if (window.localStorage.getItem("books")) {
		//if localStorage data, populate the scope
		$scope.books = JSON.parse(window.localStorage.getItem('books'));
	}

	$scope.linky = function(target, tab = "") {
		$location.url('/'+target);
		$scope.page = target;
		$scope.setTab(tab);
	}

	$scope.setTab = function(tab) {
		$scope.tab = tab;
	}

	$scope.setError = function(message={}) {
		$scope.debug("setError()");
		$scope.debug(message);
		$scope.error = message;
	}

	$scope.updateLocal = function(){
		if ($scope.user.sync !== 'false'){
			$scope.debug("updateLocal()");
			window.localStorage.setItem("user", JSON.stringify($scope.user));
			window.localStorage.setItem("books", JSON.stringify($scope.books));
		}
		
	}

	$scope.updateOnline = function(t = 'books', b = '', e = ''){
		if ($scope.user.status == 'online'){
			$scope.debug("updateOnline()");
			$http.post('/api/sync.php', {
					action: 'sync',
					target: t,
					book: b,
					entry: e,
					userid: $scope.user.id,
					books: JSON.stringify($scope.books),
					user: JSON.stringify($scope.user)
				}
			).then(
				function(res) {
					if (res.data['success'] === false){
						$scope.setError(res.data['error']);
						$scope.debug(res.data['message']);
					}else{
						console.log(res.data['message']);
						//update localStorage
						if (res.data['user']) $scope.user = res.data['user'];
						$scope.books = res.data['books'];
						$scope.updateLocal();
						$scope.linky("books", "library");
					}
					$scope.loading = false;
				},
				function(res, status) {
					$scope.setError({1: {text: "We're having trouble talking to the server, please try again later. Log out ⚙️ to switch to Local Mode."}});
					console.error(res, status);
					$scope.loading = false;
				}
			);
		}
	}

	$scope.count = function(array){
		return array.length;
	}

	//changelog and versioning
	$http.get('changelog.json')
	.then(function(res){
		let v = res.data;
		$scope.version = {
			major: v['version'].major,
			minor: v['version'].minor,
			trivial: v['version'].trivial
		};
		$scope.changelog_messages = v['messages'];
	});

}]);


//welcome Controller
import {welcome_name, welcome_function} from "./_welcome";
wBlock.controller(welcome_name, welcome_function);

//books Controller
import {books_name, books_function} from "./_books";
wBlock.controller(books_name, books_function);

//settings Controller
import {settings_name, settings_function} from "./_settings";
wBlock.controller(settings_name, settings_function);