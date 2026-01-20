export const books_name = "books";
export const books_function = ['$rootScope', '$scope', '$log', '$http', '$location', '$interval', function($rootScope, $scope, $log, $http, $location, $interval){
	$scope = $rootScope;

	$scope.debug("loading the books controller");
	// $scope.linky('books');
	// $scope.setTab("library");
	if (!$scope.page) $scope.page = "books";
	if (!$scope.tab) $scope.setTab("library");

	if (!$scope.user.status){
		$scope.linky("welcome", "intro");
	}

	//the books data model looks like this:
	/*
	{
		bookID : {
			id: bookID,
			name: bookname,
			target: 100,
			total: 50,
			latest: {timestamp:1234567890, words: 1234},
			entries: [{timestamp:1234567890, words: 1234}]
		}
	}
	*/

	$scope.getDate = function(timestamp) {
		let date = new Date(parseInt(timestamp));
		return date.toLocaleDateString();
	}

	$scope.setDate = function(datestring) {
		return Date.parse(datestring).toString();
	}

	$scope.editBook = function(bookID = 0, action = "") {
		$scope.debug("editBook("+bookID+","+action+")");
		if (action == "update"){
			//validate the number fields
			let e = {};
			let i = 1;
			if (!parseInt($scope.book.target)){
				$scope.debug("target: "+parseInt($scope.book.target));
				e[i] = {text: "Target wordcount must be a number larger than 0"};
				i++;
			}
			if (!parseInt($scope.book.total) && $scope.book.total !== 0){
				$scope.debug("total: "+parseInt($scope.book.total))
				e[i] = {text: "Current wordcount must be a number"};
			}
			if (e[1]){
				$scope.debug(e);
				$scope.setError(e);
			}else{
				$scope.books[bookID] = $scope.book;
				$scope.updateLocal();
				$scope.updateOnline();
				$scope.setTab("library");
			}
		}else if (action == 'cancel'){
			$scope.book = {};
			$scope.setTab("library");
		}else{
			if (bookID === 0) {
				$scope.debug($scope.book);
				$scope.debug("adding new book");
				let newID = Object.keys($scope.books).length + 1;
				$scope.book = {
					id: newID.toString(),
					name: "",
					target: "",
					total: 0,
					latest: {
						timestamp: "",
						words: ""
					},
					entries: []
				};
				$scope.debug($scope.book);
				window.logScope();
			}else{
				$scope.debug("editing book");
				let j = JSON.stringify($scope.books[bookID]);
				$scope.book = JSON.parse(j);
			}
			$scope.setTab("bookView");
		}
	}

	$scope.killBook = function(bookID, action = "") {
		$scope.debug("killBook("+bookID+","+action+")");
		$scope.book = {
			id: bookID,
			name: $scope.books[bookID].name
		};
		if (action == "kill") {
			delete $scope.books[bookID];
			$scope.updateLocal();
			$scope.updateOnline('kill');
			$scope.setTab("library");
		}else if (action == "cancel"){
			$scope.book = {};
			$scope.setTab("library");
		}else{
			$scope.setTab("killBook");
		}
	}

	$scope.entry = function(bookID = 0, action) {
		$scope.debug("entry("+bookID+","+action+")");
		let j = JSON.stringify($scope.books[bookID]);
		$scope.book = JSON.parse(j);
		// let d = new Date();
		// $scope.time.count = "";
		// $scope.time.date = d.toISOString().substr(0, 10);

		if (action == "view"){
			$scope.setTab("timeEntries");
		}else if (action == "new"){
			$scope.setTab("timeEntry");
		}else if (action == "add"){
			$scope.debug("newDate:"+$scope.time.date+"| newCount: "+$scope.time.count);
			//validate
			if ($scope.time.date == ''){
				$scope.setError({1:{text:"Add a date for your entry"}});
			}else if (!parseInt($scope.time.count)){
				$scope.setError({1:{text:"wordcount must be a number"}});
			}else{
				//take the new values and create an entry
				let a = new Date(); //this is to tack on the "now" time to the date so all entries in a single day dont have the same stamp
					a = Date.parse(a).toString();
					a = a.substring(8);
					a = parseInt(a);
				let b = Date.parse($scope.time.date);
				let eDate = (a + b).toString();
				$scope.debug({timeModifier: a, timeEntered: b, setDate: eDate});

				let wc = parseInt($scope.time.count) - parseInt($scope.book.total);
				let entry = {
					timestamp: eDate,
					words: wc
				}
				$scope.book.latest = entry;
				$scope.book.entries.unshift(entry);
				$scope.book.total = $scope.time.count;

				$scope.books[bookID] = $scope.book;
				$scope.updateLocal();
				$scope.updateOnline();
				$scope.setTab("library");
				$scope.book = {};
			}

		}else if (action == "cancel"){
			$scope.book = {};
			$scope.newDate = '';
			$scope.newCount = '';
			$scope.setTab("library");
		}else if (action == "back"){
			$scope.book = {};
			$scope.setTab("library");
		}
	}

	$scope.chartCap = 0;
	$scope.setChartCap = function(){
		let Earr = [];
		for (var i = $scope.book.entries.length - 1; i >= 0; i--) {
			Earr.push($scope.book.entries[i].words);
		}
		Earr = Earr.sort(function(a, b){return b - a});
		let topnum = parseInt(Earr[0]);
		let pad = Math.floor(topnum/3);
		$scope.chartCap = topnum + pad;
		$scope.debug({'Earr': Earr, 'topnum': topnum, 'pad': pad, 'charCap': $scope.chartCap});
	}

}];