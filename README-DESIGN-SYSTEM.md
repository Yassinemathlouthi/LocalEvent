# LocalEvent Design System Implementation Guide

This guide explains how to implement the new modern design system across the LocalEvent Symfony project.

## Design System Structure

The design system is organized as follows:

```
public/
  assets/
    css/
      design-system/
        variables.css   (contains color, typography, and spacing variables)
        components.css  (contains button, card, form styles, etc.)
        layout.css      (contains grid, container, dashboard layout)
        main.css        (imports all parts and provides utility classes)
    js/
      design-system.js  (interactive functions like toasts, navbar effects)
```

## Setup Instructions

1. We've already created the necessary files in the structure above.
2. We've updated the `base.html.twig` to include the design system CSS and JS files.
3. We've updated the main navigation and footer with the new design.

## Implementing in Templates

### 1. Event Cards

Replace the existing event card HTML with this modern version:

```twig
<div class="event-card">
  <div class="event-card-img">
    {% if event.image %}
      <img src="{{ vich_uploader_asset(event, 'imageFile') }}" alt="{{ event.title }}">
    {% else %}
      <div class="event-card-placeholder">
        <i class="bi bi-calendar-event"></i>
      </div>
    {% endif %}
    
    {% if eventPassed %}
      <div class="event-badge event-badge-ended">Event Ended</div>
    {% endif %}
    
    <div class="event-date-badge">
      <span class="month">{{ event.date|date('M') }}</span>
      <span class="day">{{ event.date|date('d') }}</span>
    </div>
  </div>
  
  <div class="event-card-body">
    <div class="event-card-categories">
      {% if event.categories|length > 0 %}
        {% for category in event.categories %}
          <span class="category-tag">{{ category.name }}</span>
        {% endfor %}
      {% else %}
        <span class="category-tag category-tag-neutral">Uncategorized</span>
      {% endif %}
    </div>
    
    <h3 class="event-card-title">{{ event.title }}</h3>
    <p class="event-card-desc">{{ event.description|slice(0, 120) ~ (event.description|length > 120 ? '...' : '') }}</p>
    
    <div class="event-card-footer">
      <div class="event-card-location">
        <i class="bi bi-geo-alt"></i>
        <span>{{ event.location }}</span>
      </div>
      
      <div class="event-card-actions">
        <a href="{{ path('event_show', {'id': event.id}) }}" class="btn btn-secondary btn-sm">View Details</a>
        
        {% if is_granted('ROLE_USER') and app.user != event.organizer and not eventPassed %}
          <form method="post" action="{{ path('event_join', {'id': event.id}) }}">
            <input type="hidden" name="_token" value="{{ csrf_token('join' ~ event.id) }}">
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="bi bi-calendar-check"></i> Join
            </button>
          </form>
        {% endif %}
      </div>
    </div>
  </div>
</div>
```

### 2. Forms

Update forms to use the new modern style:

```twig
<form method="post" class="modern-form">
  <div class="form-row">
    <div class="form-group">
      <label for="title">Event Title</label>
      <div class="input-wrapper">
        <i class="bi bi-type-h1"></i>
        {{ form_widget(form.title, {'attr': {'class': 'form-control'}}) }}
      </div>
      {{ form_errors(form.title) }}
    </div>
  </div>
  
  {# Continue for other form fields #}
  
  <div class="form-actions">
    <button type="button" class="btn btn-ghost" onclick="history.back()">Cancel</button>
    <button type="submit" class="btn btn-primary">Create Event</button>
  </div>
</form>
```

### 3. Dashboard Layout

For dashboard pages, use this layout structure:

```twig
<div class="dashboard-layout">
  <aside class="dashboard-sidebar">
    {% include 'partials/_dashboard_sidebar.html.twig' with {'active': 'dashboard'} %}
  </aside>
  
  <main class="dashboard-content">
    <div class="dashboard-header">
      <h1 class="page-title">Dashboard</h1>
      {% if showCreateButton %}
        <a href="{{ path('event_new') }}" class="btn btn-primary">
          <i class="bi bi-plus-circle"></i>
          <span>Create Event</span>
        </a>
      {% endif %}
    </div>
    
    <div class="dashboard-content-body">
      {# Dashboard content here #}
    </div>
  </main>
</div>
```

### 4. Flash Messages

Flash messages are now automatically handled by the design system. They appear as toasts in the top-right corner.

You can also trigger flash messages programmatically using JavaScript:

```javascript
// Show a success message
showFlashMessage('Event created successfully!', 'success');

// Show an error message
showFlashMessage('Something went wrong!', 'error');

// Show a warning message with custom duration (in milliseconds)
showFlashMessage('This action cannot be undone.', 'warning', 10000);
```

### 5. Buttons

Use the new button styles:

```twig
<!-- Primary Button -->
<button class="btn btn-primary">
  <i class="bi bi-plus-circle"></i>
  <span>Create Event</span>
</button>

<!-- Secondary Button -->
<button class="btn btn-secondary">View Details</button>

<!-- Ghost Button -->
<button class="btn btn-ghost">Cancel</button>

<!-- Small Button -->
<button class="btn btn-primary btn-sm">Small Button</button>

<!-- Danger Button -->
<button class="btn btn-danger">Delete</button>
```

### 6. Tables

For data tables, use:

```twig
<div class="table-responsive">
  <table class="table">
    <thead>
      <tr>
        <th>Title</th>
        <th>Date</th>
        <th>Location</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      {% for event in events %}
        <tr>
          <td>{{ event.title }}</td>
          <td>{{ event.date|date('M d, Y') }}</td>
          <td>{{ event.location }}</td>
          <td>
            <div class="d-flex gap-2">
              <a href="{{ path('event_show', {'id': event.id}) }}" class="btn btn-icon">
                <i class="bi bi-eye"></i>
              </a>
              <a href="{{ path('event_edit', {'id': event.id}) }}" class="btn btn-icon">
                <i class="bi bi-pencil"></i>
              </a>
            </div>
          </td>
        </tr>
      {% endfor %}
    </tbody>
  </table>
</div>
```

## Utility Classes

The design system provides many utility classes you can use:

- **Colors**: `.text-primary`, `.text-success`, `.text-danger`, `.bg-primary`, etc.
- **Spacing**: `.mt-1` through `.mt-5`, `.mb-1` through `.mb-5`, `.p-1` through `.p-5`
- **Layout**: `.d-flex`, `.flex-column`, `.align-items-center`, `.justify-content-between`
- **Text**: `.text-center`, `.text-left`, `.text-right`
- **Borders**: `.rounded`, `.rounded-lg`, `.rounded-circle`

## Page-Specific Implementations

### Home Page

Update the hero section with a more modern gradient:

```twig
<section class="hero-section">
  <div class="container">
    <div class="hero-content">
      <h1 class="hero-title">Discover Events Near You</h1>
      <p class="hero-subtitle">Join and connect with your community through local events</p>
      <div class="hero-actions">
        <a href="{{ path('event_index') }}" class="btn btn-primary">
          <i class="bi bi-calendar-event"></i>
          <span>Browse Events</span>
        </a>
        {% if not app.user %}
          <a href="{{ path('app_register') }}" class="btn btn-secondary">
            <i class="bi bi-person-plus"></i>
            <span>Sign Up Now</span>
          </a>
        {% endif %}
      </div>
    </div>
  </div>
</section>

<style>
.hero-section {
  background: linear-gradient(120deg, var(--primary-700), var(--primary-500));
  padding: 6rem 0;
  color: white;
}

.hero-content {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.hero-title {
  font-size: var(--fs-4xl);
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.hero-subtitle {
  font-size: var(--fs-xl);
  margin-bottom: 2.5rem;
  opacity: 0.9;
}

.hero-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.hero-actions .btn-secondary {
  background-color: rgba(255, 255, 255, 0.15);
  border: none;
  color: white;
}

.hero-actions .btn-secondary:hover {
  background-color: rgba(255, 255, 255, 0.25);
}
</style>
```

## Best Practices

1. **Consistency**: Use the design system components consistently throughout the application
2. **Mobile-first**: All components are designed to be responsive - let them resize naturally
3. **Icons**: Pair icons with text for better usability and visual appeal
4. **Animations**: Use subtle animations for interactions but don't overdo it
5. **Feedback**: Always provide visual feedback for user actions with toast messages

## Troubleshooting

If styles are not applied correctly:
1. Clear your browser cache
2. Make sure the CSS files are being loaded (check browser dev tools)
3. Check for any CSS conflicts
4. Try adding `!important` to override stubborn styles if necessary

## Further Customization

You can customize the design system by modifying the `variables.css` file:
- Change the primary color by updating `--primary-500` and related variables
- Adjust typography by changing font sizes in the `--fs-*` variables
- Update spacing, shadows, and more to match your brand

## Conclusion

By implementing this design system, LocalEvent will have a modern, cohesive, and visually appealing user interface that enhances the user experience. The system is designed to be easy to use and extend while maintaining consistency across the application. 