(function($) {
  $(document).ready(function() {
    // مدیریت باز و بسته شدن منوها (به جز تقویم)
    $(document).on('click', '.dropdown-toggle:not(.datepicker)', function(e) {
      e.stopPropagation();
      e.preventDefault();
      
      var $menu = $(this).next('.dropdown-menu');
      var isOpen = $menu.is(':visible');
      
      // بستن تمام منوهای دیگر (به جز تقویم)
      $('.dropdown-menu').not($menu).not('.datepicker-dropdown').hide();
      
      // باز/بسته کردن منوی جاری
      $menu.toggle(!isOpen);
    });

    // بستن منوها با کلیک روی گزینه‌ها (به جز گزینه‌های تقویم)
    $(document).on('click', '.dropdown-menu a:not(.datepicker-dropdown a), .dropdown-menu li:not(.datepicker-dropdown li)', function(e) {
      e.stopPropagation();
      
      // بستن منوی والد
      $(this).closest('.dropdown-menu').hide();
    });

    // بستن منوها با کلیک خارج از آنها (استثنا برای تقویم)
    $(document).on('click', function(e) {
      // اگر روی تقویم کلیک شده، کاری نکن
      if ($(e.target).closest('.datepicker-dropdown').length) {
        return;
      }
      
      // اگر روی toggle منوهای دیگر کلیک نشده، همه منوها را ببند
      if (!$(e.target).closest('.dropdown').length) {
        $('.dropdown-menu').not('.datepicker-dropdown').hide();
      }
    });
    
    // مدیریت ویژه برای تقویم
    $(document).on('click', '.datepicker input, .datepicker .input-group-addon', function(e) {
      e.stopPropagation();
      
      // پیدا کردن تقویم مربوطه
      var $datepicker = $(this).closest('.datepicker');
      var $dropdown = $datepicker.find('.datepicker-dropdown');
      
      // بستن تمام تقویم‌های دیگر
      $('.datepicker-dropdown').not($dropdown).hide();
      
      // نمایش تقویم جاری
      $dropdown.toggle();
    });
  });
})(jQuery);