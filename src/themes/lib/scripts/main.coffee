$ ->

    $nivo_field  = $('.js-nivo-table').closest('.holder')
    $nivo_table  = $('.js-nivo-table tbody')
    $nivo_templ  = $('.js-nivo-slide-template')
    $nivo_empty  = $('.js-nivo-no-slides')
    $slide_count = $('[name=slide_count]')
    use_assets   = $('.js-nivo-table').data('assets')


    #
    # EE File Browser - Add Image
    #
    handleFilePicked = (file, field_name) ->
        return unless file.is_image

        $field = $("#{field_name}").closest(".file_field")

        $field.find(".file_set").show()
        $field.find(".choose_file").hide()
        $field.find(".undo_remove").hide()
        $field.find('[name*="_hidden_file"]').val(file.file_name)
        $field.find('[name*="_hidden_dir"], [name*="_directory"]').val(file.upload_location_id)
        $field.find(".filename").html("<img src=\"#{file.thumb}\"><br>#{file.file_name}")

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

        # Show row - This needs to be done before Assets can be setup on this
        #  field.
        $new_row.removeClass('is-hidden')

        # Initialize the file browser for this row. Normally a deep clone of the
        #  row would copy the file_browser events with it, but because we are
        #  updating the name of the fields, the cloned events can't find the
        #  right elements to update.
        if use_assets
            $assets_field = $('.assets-field', $new_row)
            field_id = 'slide_image_'+row_id
            $assets_field.attr 'id', field_id
            new Assets.Field field_id, field_id,
                filedirs: 'all'
                multi: false
                view: 'thumbs'
        else
            field_name   = "[name=slide_image_#{row_id}]"
            $file_input  = $(field_name)
            $file_field  = $file_input.closest(".file_field")
            $choose_file = $file_field.find(".choose_file")
            $no_file     = $file_field.find(".no_file")
            file_cache   = []
            field_data   =
                content_type: $file_input.data 'content-type'
                directory:    $file_input.data 'directory'

            $.ee_filebrowser.add_trigger($choose_file, field_name, field_data, handleFilePicked)

            $file_selector = if $choose_file.length then $choose_file else $no_file

            $file_field.find(".remove_file").click ->
                $file_selector.show()
                $file_field.find(".file_set").hide()
                $file_field.find(".sub_filename a").show()

                $file_field.find("input[type=hidden]").val (i, value) ->
                    file_cache[i] = value
                    return ""

                false

            $file_field.find(".undo_remove").click ->
                $file_selector.hide()
                $file_field.find(".file_set").show()
                $file_field.find(".sub_filename a").hide()

                $file_field.find("input[type=hidden]").val (i) ->
                    return file_cache[i] or ""

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
    if $nivo_table.length > 0
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
            $slide = $(this)
            row_id = i + 1

            $slide.data('index', row_id)
            $('[name]', $slide).each (j) ->
                $field = $(this)
                $field.attr('name', $field.attr('name').replace(/\d+/, row_id))
            $('.assets-field', $slide).attr 'id', 'slide_image_'+row_id

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
