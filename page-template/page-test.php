<?php
/* Template Name: тестовая страница */
get_header();
?>

<div class="container">
    <div class="weather-widget border-right">
        <div class="weather-current">
            <div>
                <div class="weather-current__location">Кишииёв, сейчас</div>
                <span class="weather-current__value">+9°C</span>
            </div>

            <div class="weather-current__temp">
                <div class="weather-current__description">Переменная облачность</div>
                <svg class="weather-current__icon" width="54" height="48">
                    <use href="#partly-cloudy"></use>
                </svg>
            </div>


            <div class="weather-current__details">
                <div class="weather-current__detail">
                    <span>Ветер</span>
                    <span>2 м/с, ЮОВ</span>
                </div>
                <div class="weather-current__detail">
                    <span>Атмосферное давление</span>
                    <span>761 мм рт. ст.</span>
                </div>
                <div class="weather-current__detail">
                    <span>Геомагнитная активность</span>
                    <span>2 балла</span>
                </div>
            </div>
        </div>

        <div class="weather-forecast">
            <div class="weather-forecast__title">Популярные населённые пункты в Молдове</div>
            <div class="weather-forecast__list">
                <div class="weather-forecast__item">
                    <div class="weather-forecast__city">Бельцы</div>
                    <div class="weather-forecast__temp">+1</div>
                    <svg class="weather-forecast__icon" width="24" height="24">
                        <use href="#cloudy-rain"></use>
                    </svg>
                </div>
                <div class="weather-forecast__item">
                    <div class="weather-forecast__city">Бендеры</div>
                    <div class="weather-forecast__temp">0</div>
                    <svg class="weather-forecast__icon" width="24" height="24">
                        <use href="#partly-cloudy"></use>
                    </svg>
                </div>
                <div class="weather-forecast__item">
                    <div class="weather-forecast__city">Единцы</div>
                    <div class="weather-forecast__temp">-2</div>
                    <svg class="weather-forecast__icon" width="24" height="24">
                        <use href="#cloudy-rain"></use>
                    </svg>
                </div>
                <div class="weather-forecast__item">
                    <div class="weather-forecast__city">Маргялешты</div>
                    <div class="weather-forecast__temp">+1</div>
                    <svg class="weather-forecast__icon" width="24" height="24">
                        <use href="#cloudy-rain"></use>
                    </svg>
                </div>
                <div class="weather-forecast__item">
                    <div class="weather-forecast__city">Чадыр-Лунга</div>
                    <div class="weather-forecast__temp">0</div>
                    <svg class="weather-forecast__icon" width="24" height="24">
                        <use href="#partly-cloudy"></use>
                    </svg>
                </div>
                <div class="weather-forecast__item">
                    <div class="weather-forecast__city">Оргеев</div>
                    <div class="weather-forecast__temp">+0</div>
                    <svg class="weather-forecast__icon" width="24" height="24">
                        <use href="#cloudy"></use>
                    </svg>
                </div>
                <div class="weather-forecast__item">
                    <div class="weather-forecast__city">Комрат</div>
                    <div class="weather-forecast__temp">+8</div>
                    <svg class="weather-forecast__icon" width="24" height="24">
                        <use href="#snow"></use>
                    </svg>
                </div>
                <div class="weather-forecast__item">
                    <div class="weather-forecast__city">Кишииёв аэропорт</div>
                    <div class="weather-forecast__temp">+1</div>
                    <svg class="weather-forecast__icon" width="24" height="24">
                        <use href="#cloudy"></use>
                    </svg>
                </div>
                <div class="weather-forecast__item">
                    <div class="weather-forecast__city">Чадыр-Лунга</div>
                    <div class="weather-forecast__temp">0</div>
                    <svg class="weather-forecast__icon" width="24" height="24">
                        <use href="#cloudy"></use>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>