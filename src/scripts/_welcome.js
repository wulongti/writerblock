export const welcome_name = "welcome";
export const welcome_function = ['$rootScope', '$scope', '$log', '$http', '$location', '$interval', function($rootScope, $scope, $log, $http, $location, $interval){
	$scope = $rootScope;

	$scope.debug("loading the Welcome controller");
	// $scope.linky("welcome");
	// $scope.setTab("intro");
	if (!$scope.page) $scope.page = "welcome";
	if (!$scope.tab) $scope.setTab("intro");

	//if the user has an account and is logged in then skip the welcome stuff
	if ($scope.user.status == "online"){
		$scope.linky("books");
	}
	
	$scope.account = function(){
		$scope.debug("account submit:");
		$scope.debug($scope.accountform);
		//{email: "bob@bob.com", pass: "asdf", pass2: "asdf", name: "bobName", newsletter: "true"}
		//validate the form
		let e = {};
		let i = 1;
		let epat = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
		if (!epat.test($scope.accountform.email)){
			$scope.debug("email: "+$scope.accountform.email);
			e[i] = {text: "Enter a valid email address"};
			i++;
		}
		if ($scope.accountform.pass !== $scope.accountform.pass2){
			$scope.debug($scope.accountform.pass+" !== "+$scope.accountform.pass2);
			e[i] = {text: "Passwords do not match"};
			i++;
		}
		if ($scope.accountform.pass.length < 8){
			$scope.debug($scope.accountform.pass);
			e[i] = {text: "Password must be at least 8 characters long"};
			i++;
		}
		if ($scope.accountform.name.length === 0){
			$scope.debug($scope.accountform.name);
			e[i] = {text: "Display Name can not be blank"};
			i++;
		}

		if (e[1]){
			$scope.debug(e);
			$scope.setError(e);
		}else{
			$scope.loading = true;
			// we pass the validation, lets push the data to the DB
			$http.post('/api/sync.php', {
					action: 'newAccount',
					email: $scope.accountform.email,
					name: $scope.accountform.name,
					pass: $scope.accountform.pass,
					newsletter: $scope.accountform.newsletter,
					books: JSON.stringify($scope.books),
				}
			).then(
				function(res) {
					if (res.data['success'] == false){
						$scope.setError(res.data['error']);
						$scope.debug(res.data['message']);
					}else{
						console.log(res.data['message']);
						//update localStorage
						$scope.user = res.data['user'];
						$scope.books = res.data['books'];
						$scope.linky("settings", "main");
					}
					$scope.loading = false;
				},
				function(res, status) {
					$scope.setError({1: {text: "We're having trouble talking to the server, please try again later"}});
					console.error(res, status);
					$scope.loading = false;
				}
			);
		}
	}

	$scope.logout = function(){
		$scope.debug("logout");
		if ($scope.user.wipe == "true"){
			$scope.user = {};
			$scope.books = {};
			localStorage.removeItem('user');
			localStorage.removeItem('books');
		}else{
			$scope.user.status = "local";
		}
		$scope.linky("welcome", "intro");		
	}

	$scope.login = function(){
		$scope.debug("login submit:");
		$scope.debug($scope.loginform);
		//validate the inputs
		let e = {};
		let i = 1;
		let epat = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
		if (typeof $scope.loginform.email === 'undefined' || !epat.test($scope.loginform.email)){
			$scope.debug("email: "+$scope.loginform.email);
			e[i] = {text: "Enter a valid email address"};
			i++;
		}
		if (typeof $scope.loginform.pass === 'undefined' || $scope.loginform.pass.length < 1){
			$scope.debug($scope.loginform.pass);
			e[i] = {text: "Password can not be blank"};
			i++;
		}
		if (e[1]){
			$scope.debug(e);
			$scope.setError(e);
		}else{
			$scope.loading = true;
			// we pass the validation, lets push the data to the DB
			$http.post('/api/sync.php', {
					action: 'login',
					email: $scope.loginform.email,
					pass: $scope.loginform.pass,
					books: JSON.stringify($scope.books),
					remember: $scope.loginform.remember
				}
			).then(
				function(res) {
					if (res.data['success'] === false){
						$scope.setError(res.data['error']);
						$scope.debug(res.data['message']);
					}else{
						console.log(res.data['message']);
						//update localStorage
						$scope.user = res.data['user'];
						$scope.books = res.data['books'];
						$scope.updateLocal();
						$scope.linky("books", "library");
						$scope.loginform = {};
					}
					$scope.loading = false;
				},
				function(res, status) {
					$scope.setError({1: {text: "We're having trouble talking to the server, please try again later"}});
					console.error(res, status);
					$scope.loading = false;
				}
			);
		}
	}

	$scope.passreset = function(email) {
		$scope.debug("email sent to "+email);
		/*******************/
		//send pass reset email
		$scope.setTab('reset');
	}

	$scope.localOnly = function(){
		$scope.user = {
			name: "",
			email: "user@local.storage",
			status: "local",
			sync: true,
			share: false,
			wipe: false
		};
		window.localStorage.setItem("user", JSON.stringify($scope.user));
		$scope.linky("books", "library");
	}
}];
