function toggle_course_seats() {
    if ($('#cs-course-seats').css('display') == 'block') {
        $('#cs-course-seats').css('display', 'none');
        $('#cs-course-seat-toggleimg').attr('src', $('#cs-course-seat-toggleimg').attr('src').replace('minus', 'plus'));
    } else {
        $('#cs-course-seats').css('display', 'block');
        $('#cs-course-seat-toggleimg').attr('src', $('#cs-course-seat-toggleimg').attr('src').replace('plus', 'minus'));
    }
}