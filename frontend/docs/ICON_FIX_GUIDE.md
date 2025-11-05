# Исправление проблемы со смещением/исчезновением иконок

## Проблема

При hover на карточки, кнопки и другие элементы с `transform` анимацией, иконки:
- Смещались в сторону
- Исчезали полностью
- Меняли свое положение
- Восстанавливались только после убирания курсора

## Корень проблемы

1. **Transform наследование**: При `transform: translateY(-2px)` на родителе, дочерние иконки наследовали transform
2. **Rendering issues**: PrimeVue иконки рендерятся как inline элементы без фиксированных размеров
3. **GPU acceleration**: Отсутствие `translateZ(0)` приводило к flickering
4. **Stacking context**: Иконки не создавали свой stacking context

## Решение

### 1. Глобальный фикс (frontend/src/assets/styles/main.css)

```css
/* Все иконки получают фиксированные стили */
i, .pi, [class^="pi-"], [class*=" pi-"] {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  flex-shrink: 0 !important;
  line-height: 1 !important;
  vertical-align: middle !important;
  font-style: normal !important;
  will-change: transform !important;
  transform: translateZ(0) !important; /* GPU acceleration */
  backface-visibility: hidden !important; /* No flickering */
  position: relative !important; /* Stacking context */
}

/* Сброс transform при hover родителя */
*:hover i,
*[style*="transform"] i {
  transform: translateZ(0) !important;
}
```

### 2. Локальные фиксы в компонентах

Для компонентов с особыми требованиями:

```css
/* TaskCard */
.task-card__badge i,
.task-card__due-date i,
.task-card__subtasks i,
.task-card__recurrence i {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 0.875rem; /* Фиксированная ширина */
  height: 0.875rem; /* Фиксированная высота */
}

/* QuickFilters */
.quick-filter-btn i {
  width: 1em;
  height: 1em;
}
```

## Затронутые компоненты

### ✅ Исправлено:
1. **TaskCard** - иконки в карточках задач
2. **QuickFilters** - иконки в быстрых фильтрах
3. **LoginForm** - иконки в форме входа (глобальный фикс)
4. **RegisterForm** - иконки в форме регистрации (глобальный фикс)
5. **ProfileView** - иконки в профиле (глобальный фикс)
6. **TaskDetailsSidebar** - иконки в деталях задачи
7. **TaskFilters** - иконки в фильтрах
8. **CalendarView** - иконки в календаре

## Тестирование

### Проверьте следующие сценарии:

1. ✅ Наведение на карточку задачи - иконки НЕ смещаются
2. ✅ Наведение на кнопку фильтра - иконки НЕ исчезают
3. ✅ Наведение на форму входа - иконки НЕ сдвигаются
4. ✅ Перезагрузка страницы - иконки в правильных позициях
5. ✅ Открытие сайдбара - иконки не "прыгают"
6. ✅ Hover на любой элемент с анимацией - иконки стабильны

## Best Practices для новых компонентов

### При добавлении иконок:

```vue
<!-- ✅ Правильно -->
<button class="my-button">
  <i class="pi pi-check" />
  <span>Текст</span>
</button>

<style>
.my-button i {
  width: 1em;
  height: 1em;
  flex-shrink: 0;
}
</style>
```

```vue
<!-- ❌ Неправильно -->
<button class="my-button">
  <i class="pi pi-check"></i>
  <span>Текст</span>
</button>

<style>
/* Нет стилей для иконки - может сломаться */
</style>
```

### При использовании transform:

```css
/* ✅ Правильно - иконка не наследует transform */
.card:hover {
  transform: translateY(-2px);
}
.card i {
  transform: translateZ(0) !important;
}

/* ❌ Неправильно - иконка наследует transform и сдвигается */
.card:hover {
  transform: translateY(-2px);
}
```

## Примечания

- `!important` необходим для переопределения PrimeVue стилей
- `translateZ(0)` включает GPU acceleration и предотвращает flickering
- `backface-visibility: hidden` убирает мерцание при анимациях
- `flex-shrink: 0` предотвращает сжатие иконок во flexbox
- `position: relative` создает свой stacking context

## Если проблема повторится

1. Проверьте, что иконка имеет фиксированные `width` и `height`
2. Убедитесь, что родитель не имеет `transform` без сброса для иконки
3. Добавьте `transform: translateZ(0)` для иконки
4. Проверьте CSS specificity - может понадобиться `!important`





