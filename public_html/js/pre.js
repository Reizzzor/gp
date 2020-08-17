var gotY = getCookie('scr_y');
var gotX = getCookie('scr_x');
var real_y = window.innerHeight;
var real_x = window.innerWidth;
if(!gotY || !gotX || gotY != real_y || gotX != real_x) cookiesRequest('screen_size', {scr_x: real_x, scr_y: real_y});
