
module.exports = (grunt) ->

    grunt.loadNpmTasks 'grunt-contrib-clean'
    grunt.loadNpmTasks 'grunt-contrib-copy'
    grunt.loadNpmTasks 'grunt-contrib-coffee'
    grunt.loadNpmTasks 'grunt-contrib-uglify'
    grunt.loadNpmTasks 'grunt-contrib-stylus'
    grunt.loadNpmTasks 'grunt-autoprefixer'
    grunt.loadNpmTasks 'grunt-contrib-cssmin'
    grunt.loadNpmTasks 'grunt-contrib-imagemin'
    grunt.loadNpmTasks 'grunt-contrib-watch'


    grunt.initConfig

        pkg: grunt.file.readJSON 'package.json'

        clean:
            build: ['public']

        copy:
            fonts:
                files: [
                    expand: true
                    cwd: 'lib/fonts'
                    src: ['**/*']
                    dest: 'public/fonts/'
                ]
            images:
                files: [
                    expand: true
                    cwd: 'lib/images'
                    src: ['**/*', '!**/*.{png,jpg,jpeg,gif}']
                    dest: 'public/images/'
                ]
            assets:
                files: [
                    expand: true
                    cwd: 'lib/nivo-slider'
                    src: ['**']
                    dest: 'public/nivo-slider/'
                ]

        coffee:
            options:
                bare: true
            build:
                files:
                    'public/<%= pkg.name %>.js': ['lib/scripts/main.coffee']

        uglify:
            options:
                mangle: true
                compress: true
                preserveComments: false
            build:
                files:
                    'public/jquery.tablednd.js': ['lib/scripts/jquery.tablednd.js']
                    'public/<%= pkg.name %>.js': ['public/<%= pkg.name %>.js']

        stylus:
            options:
                paths: ['lib/styles']
                urlfunc: 'datauri'
                import: ['nib']
                define:
                    'vendor-prefixes': ['official']
            build:
                files:
                    'public/<%= pkg.name %>.css': ['lib/styles/main.styl']

        autoprefixer:
            options:
                browsers: ['> 5%', 'last 2 versions']
            build:
                files:
                    'public/<%= pkg.name %>.css': ['public/<%= pkg.name %>.css']

        cssmin:
            build:
                files:
                    'public/<%= pkg.name %>.css': ['public/<%= pkg.name %>.css']

        imagemin:
            options:
                optimizationLevel: 3
            build:
                files: [
                    expand: true
                    cwd: 'lib/images'
                    src: ['**/*.{png,jpg,jpeg,gif}']
                    dest: 'public/images/'
                ]

        watch:
            fonts:
                files: 'lib/fonts/**/*'
                tasks: ['copy:fonts']
            scripts:
                files: 'lib/scripts/**/*'
                tasks: ['scripts']
                options:
                    interupt: true
            styles:
                files: 'lib/styles/**/*'
                tasks: ['styles']
                options:
                    interupt: true
            imageoptim:
                files: 'lib/images/**/*.{png,jpg,jpeg,gif}'
                tasks: ['imagemin']
            images:
                files: ['lib/images/**/*', '!lib/images/**/*.{png,jpg,jpeg,gif}']
                tasks: ['copy:images']


    grunt.registerTask 'scripts', ['coffee', 'uglify']
    grunt.registerTask 'styles', ['stylus', 'autoprefixer', 'cssmin']
    grunt.registerTask 'build', ['clean', 'copy', 'scripts', 'styles', 'imagemin']
    grunt.registerTask 'default', ['build']
