$ ->

    $nivo_field  = $('.js-nivo-table').closest('.holder')
    $nivo_table  = $('.js-nivo-table tbody')
    $nivo_templ  = $('.js-nivo-slide-template')
    $nivo_empty  = $('.js-nivo-no-slides')
    $slide_count = $('[name=slide_count]')


    #
    # Add Slide
    #
    $('.js-nivo-add-slide').on 'click', (e) ->
        e.preventDefault()

        # Hide 'no slides'
        $nivo_empty.addClass('is-hidden')

        # Clone the slide template
        $new_row = $nivo_templ.clone().appendTo($nivo_table)
            .removeClass('js-nivo-slide-template')
            .addClass('js-nivo-slide')

        # Get row ID
        row_id = $('.js-nivo-slide', $nivo_table).length
        $slide_count.val(row_id)

        # Update the name on all fields
        $('[name]', $new_row).each (i) ->
            $field = $(this)
            $field.attr('name', $field.attr('name').replace('#', row_id))

        # Initialize the file browser for this row. Normally a deep clone of the
        #  row would copy the file_browser events with it, but because we are
        #  updating the name of the fields, the cloned events can't find the
        #  right elements to update.
        file_field = "slide_image_#{row_id}"
        $file_field = $(file_field)
        $.ee_filebrowser.add_trigger $('.choose_file', $new_row), file_field, {
                content_type: $file_field.data 'content-type'
                directory:    $file_field.data 'directory'
            },
            # Callback for when an image is selected
            (file, field) ->
                directory   = file.upload_location_id
                name        = file.file_name
                thumb       = file.thumb
                $thumb      = $('.file_set', $new_row)
                $field_dir  = $("[name=#{file_field}_hidden_dir]")
                $field_file = $("[name=#{file_field}_hidden]")

                # Validation
                return if not (directory and name)

                # Update the input values
                $field_dir.val(directory)
                $field_file.val(name)

                # Update 'remove image'
                $('.remove_file', $new_row).on 'click', (e) ->
                    $thumb.addClass('js_hide')
                    $field_dir.val('')
                    $field_file.val('')

                # Load the new thumbnail
                $('img', $thumb).attr('src', thumb);
                $thumb.removeClass('js_hide')

        # Show row
        $new_row.removeClass('is-hidden')

        # Prevent default
        false


    #
    # Remove Slide
    #
    $nivo_table.on 'click', '.js-nivo-remove-slide', (e) ->
        e.preventDefault()

        # Remove this slide
        $(this).closest('.js-nivo-slide').remove()

        # Update row count
        $slide_count.val($('.js-nivo-slide', $nivo_table).length)

        # Show 'no slides' if there are none
        if not $('.js-nivo-slide').length
            $nivo_empty.removeClass('is-hidden')
        # Update field names
        else
            update_field_names()

        # Prevent default
        false


    #
    # Re-order Slides
    #
    $nivo_table.tableDnD({
        dragHandle: '.js-reorder-handle'
        onDragClass: 'is-dragging'
        onDrop: ->
            update_field_names()
    })


    #
    # Remove template on submit
    #
    $('#publishForm').on 'submit', (e) ->
        $nivo_templ.remove()


    #
    # Update Field Names
    #
    update_field_names = ->
        count = 0
        $('.js-nivo-slide', $nivo_table).each (i) ->
            $(this).data('index', i + 1)
            $('[name]', $(this)).each (j) ->
                $field = $(this)
                $field.attr('name', $field.attr('name').replace(/\d+/, i + 1))

    update_field_names()


    #
    # Toggle Settings
    #
    $('.js-nivo-field-label').on 'click', (e) ->
        $label = $(this)
        $img = $('img', $label)

        if $img.attr('src').indexOf('field_collapse') > 0
            $img.attr('src', $img.attr('src').replace('field_collapse', 'field_expand'))
            $label.next('.js-nivo-field-pane').slideDown()

        else
            $img.attr('src', $img.attr('src').replace('field_expand', 'field_collapse'))
            $label.next('.js-nivo-field-pane').slideUp()


    #
    # Conditionally display settings
    #
    $('[data-condition]').each (i) ->
        $td           = $(this)
        $tr           = $td.closest 'tr'
        [target, val] = $td.data('condition').split('=')
        $target       = $("[name='#{target}']")
        re            = new RegExp("^#{val}")

        # Namespace event to avoid collisions if multiple listeners
        $target.on "change.id_#{i}", (e) ->
            # Get the val (different for different types of inputs)
            if $target.is('select')
                val = $target.val()
            else if $target.attr('type') is 'radio'
                val = $target.filter(':checked').val()

            # Check against the target value
            if re.test val
                $tr.show()
            else
                $tr.hide()

        $target.trigger "change.id_#{i}"
