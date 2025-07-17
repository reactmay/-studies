let day = prompt("Введите день Вашего рождения");
let month = prompt("Введите месяц Вашего рождения");
let year = prompt("Введите год Вашего рождения");

function getWeekDay(date) {
  let days = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];

  return days[date.getDay()];
}

let date = new Date(year, month-1, day);

console.log('Вы родились в день недели: '+getWeekDay(date));

const getLeapYear = year => year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0);

if (getLeapYear(year)) {
	console.log('Ваш год рождения: '+year+' високосный');
} else {
	console.log('Ваш год рождения: '+year+' не високосный');
}

let now = new Date(); //Текущя дата
let today = new Date(now.getFullYear(), now.getMonth(), now.getDate()); //Текущя дата без времени
let birthdayCurrentYear = new Date(today.getFullYear(), date.getMonth(), date.getDate()); //ДР в текущем году
let age; //Возраст

//Возраст = текущий год - год рождения
age = today.getFullYear() - date.getFullYear();
//Если ДР в этом году ещё предстоит, то вычитаем из age один год
if (today < birthdayCurrentYear) {
  age = age-1;
}

console.log('Ваш Возраст: '+age);