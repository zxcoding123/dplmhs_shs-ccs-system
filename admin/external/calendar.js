(function($) {

    "use strict";

    $(document).ready(function() {

        function c(passed_month, passed_year, calNum) {
            var calendar = calNum == 0 ? calendars.cal1 : calendars.cal2;
            makeWeek(calendar.weekline);
            calendar.datesBody.empty();
            var calMonthArray = makeMonthArray(passed_month, passed_year);
            var r = 0;
            var u = false;
            while(!u) {
                if(daysArray[r] == calMonthArray[0].weekday) { 
                    u = true; 
                } else { 
                    calendar.datesBody.append('<div class="blank"></div>');
                    r++;
                }
            } 
            for(var cell=0; cell<42-r; cell++) { // 42 date-cells in calendar
                if(cell >= calMonthArray.length) {
                    calendar.datesBody.append('<div class="blank"></div>');
                } else {
                    var shownDate = calMonthArray[cell].day;
                    var iter_date = new Date(passed_year, passed_month, shownDate); 
                    var m = (shownDate != today.getDate() && passed_month == today.getMonth() 
                            || passed_month != today.getMonth() && iter_date < today)
                            ? '<div class="past-date">' : checkToday(iter_date) ? '<div class="today">' : "<div>";
                    calendar.datesBody.append(m + shownDate + "</div>");
                }
            }

            calendar.calHeader.find("h2").text(i[passed_month]+" "+passed_year);

            var clicked = false;
            selectDates(selected);

            clickedElement = calendar.datesBody.find('div');
            clickedElement.on("click", function(){
                clicked = $(this);
                if (clicked.hasClass('past-date')) { return; }
                var whichCalendar = calendar.name;

                if (firstClick && secondClick) {
                    thirdClicked = getClickedInfo(clicked, calendar);
                    var firstClickDateObj = new Date(firstClicked.year, firstClicked.month, firstClicked.date);
                    var secondClickDateObj = new Date(secondClicked.year, secondClicked.month, secondClicked.date);
                    var thirdClickDateObj = new Date(thirdClicked.year, thirdClicked.month, thirdClicked.date);
                    if (secondClickDateObj > thirdClickDateObj && thirdClickDateObj > firstClickDateObj) {
                        secondClicked = thirdClicked;
                        bothCals.find(".calendar_content").find("div").each(function(){
                            $(this).removeClass("selected");
                        });
                        selected = {};
                        selected[firstClicked.year] = {};
                        selected[firstClicked.year][firstClicked.month] = [firstClicked.date];
                        selected = addChosenDates(firstClicked, secondClicked, selected);
                    } else {
                        selected = {};
                        firstClicked = [];
                        secondClicked = [];
                        firstClick = false;
                        secondClick = false;
                        bothCals.find(".calendar_content").find("div").each(function(){
                            $(this).removeClass("selected");
                        });    
                    }
                }

                if (!firstClick) {
                    firstClick = true;
                    firstClicked = getClickedInfo(clicked, calendar);
                    selected[firstClicked.year] = {};
                    selected[firstClicked.year][firstClicked.month] = [firstClicked.date];
                } else {
                    secondClick = true;
                    secondClicked = getClickedInfo(clicked, calendar);
                    var firstClickDateObj = new Date(firstClicked.year, firstClicked.month, firstClicked.date);
                    var secondClickDateObj = new Date(secondClicked.year, secondClicked.month, secondClicked.date);

                    if (firstClickDateObj > secondClickDateObj) {
                        var cachedClickedInfo = secondClicked;
                        secondClicked = firstClicked;
                        firstClicked = cachedClickedInfo;
                        selected = {};
                        selected[firstClicked.year] = {};
                        selected[firstClicked.year][firstClicked.month] = [firstClicked.date];
                    } else if (firstClickDateObj.getTime() == secondClickDateObj.getTime()) {
                        selected = {};
                        firstClicked = [];
                        secondClicked = [];
                        firstClick = false;
                        secondClick = false;
                        $(this).removeClass("selected");
                    }

                    selected = addChosenDates(firstClicked, secondClicked, selected);
                }
                selectDates(selected);
            });

            // Add random attendance marking
            var attendanceData = generateRandomAttendance(passed_month, passed_year, calMonthArray.length);
            markAttendance(attendanceData, calendar.datesBody);
        }

        function generateRandomAttendance(month, year, daysInMonth) {
            var attendance = {};
            for (var i = 1; i <= daysInMonth; i++) {
                var status = Math.random() > 0.5 ? 'present' : 'absent';
                attendance[i] = status;
            }
            return attendance;
        }

        function markAttendance(attendanceData, datesBody) {
            datesBody.find('div').each(function(index) {
                var day = parseInt($(this).text());
                if (attendanceData[day]) {
                    $(this).addClass(attendanceData[day]);
                    $(this).append(`<span class="attendance-mark">${attendanceData[day] === 'present' ? '✓' : '✗'}</span>`);
                }
            });
        }

        function selectDates(selected) {
            if (!$.isEmptyObject(selected)) {
                var dateElements1 = datesBody1.find('div');
                var dateElements2 = datesBody2.find('div');

                function highlightDates(passed_year, passed_month, dateElements) {
                    if (passed_year in selected && passed_month in selected[passed_year]) {
                        var daysToCompare = selected[passed_year][passed_month];
                        for (var d in daysToCompare) {
                            dateElements.each(function(index) {
                                if (parseInt($(this).text()) == daysToCompare[d]) {
                                    $(this).addClass('selected');
                                }
                            });    
                        }
                    }
                }

                highlightDates(year, month, dateElements1);
                highlightDates(nextYear, nextMonth, dateElements2);
            }
        }

        function makeMonthArray(passed_month, passed_year) {
            var e=[];
            for(var r=1;r<getDaysInMonth(passed_year, passed_month)+1;r++) {
                e.push({day: r, weekday: daysArray[getWeekdayNum(passed_year,passed_month,r)]});
            }
            return e;
        }

        function makeWeek(week) {
            week.empty();
            for(var e=0;e<7;e++) { 
                week.append("<div>"+daysArray[e].substring(0,3)+"</div>") 
            }
        }

        function getDaysInMonth(currentYear,currentMon) {
            return(new Date(currentYear,currentMon+1,0)).getDate();
        }

        function getWeekdayNum(e,t,n) {
            return(new Date(e,t,n)).getDay();
        }

        function checkToday(e) {
            var todayDate = today.getFullYear()+'/'+(today.getMonth()+1)+'/'+today.getDate();
            var checkingDate = e.getFullYear()+'/'+(e.getMonth()+1)+'/'+e.getDate();
            return todayDate==checkingDate;
        }

        function getAdjacentMonth(curr_month, curr_year, direction) {
            var theNextMonth;
            var theNextYear;
            if (direction == "next") {
                theNextMonth = (curr_month + 1) % 12;
                theNextYear = (curr_month == 11) ? curr_year + 1 : curr_year;
            } else {
                theNextMonth = (curr_month == 0) ? 11 : curr_month - 1;
                theNextYear = (curr_month == 0) ? curr_year - 1 : curr_year;
            }
            return [theNextMonth, theNextYear];
        }

        function b() {
            today = new Date;
            year = today.getFullYear();
            month = today.getMonth();
            var nextDates = getAdjacentMonth(month, year, "next");
            nextMonth = nextDates[0];
            nextYear = nextDates[1];
        }

        var e = 480;

        var today;
        var year,
            month,
            nextMonth,
            nextYear;

        var r = [];
        var i = ["January","February","March","April","May","June","July","August","September","October","November","December"];
        var daysArray = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
        var o = ["#16a085","#1abc9c","#c0392b","#27ae60","#FF6860","#f39c12","#f1c40f","#e67e22","#2ecc71","#e74c3c","#d35400","#2c3e50"];
        
        var cal1=$("#calendar_first");
        var calHeader1=cal1.find(".calendar_header");
        var weekline1=cal1.find(".calendar_weekdays");
        var datesBody1=cal1.find(".calendar_content");

        var cal2=$("#calendar_second");
        var calHeader2=cal2.find(".calendar_header");
        var weekline2=cal2.find(".calendar_weekdays");
        var datesBody2=cal2.find(".calendar_content");

        var bothCals = $(".calendar");

        var calendars = {
            "cal1": { 
                "name": "first", 
                "calHeader": calHeader1, 
                "weekline": weekline1, 
                "datesBody": datesBody1 
            },
            "cal2": { 
                "name": "second", 
                "calHeader": calHeader2, 
                "weekline": weekline2, 
                "datesBody": datesBody2 
            }
        }

        var clickedElement, firstClicked, secondClicked, thirdClicked;
        var firstClick = false;
        var secondClick = false;
        var selected = {};

        b();
        c(month, year, 0);
        c(nextMonth, nextYear, 1);

        $("#calendar_first").on("click", ".switch-month", function() {
            var clicked = $(this);
            var generateNewMonth = clicked.hasClass("left") ? getAdjacentMonth(month, year, "previous") : getAdjacentMonth(month, year, "next");
            month = generateNewMonth[0];
            year = generateNewMonth[1];
            c(month, year, 0);
        });

        $("#calendar_second").on("click", ".switch-month", function() {
            var clicked = $(this);
            var generateNewMonth = clicked.hasClass("left") ? getAdjacentMonth(nextMonth, nextYear, "previous") : getAdjacentMonth(nextMonth, nextYear, "next");
            nextMonth = generateNewMonth[0];
            nextYear = generateNewMonth[1];
            c(nextMonth, nextYear, 1);
        });
    });

})(jQuery);
