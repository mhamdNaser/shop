module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    uglify: {
      options: {
				mangle: false
      },
      build: {
				files: [{
					expand: true,
					cwd: "inc/js/",
					src: ["*.js", "!*.min.js", "*/*.js", "!*/*.min.js"],
					dest: "inc/js",
					rename: function (dst, src) {
						return dst + '/' + src.replace('.js', '.min.js');
					}
				}]
      }
    },
    copy: {
			leafletjs: {
				src: ["node_modules/leaflet/dist/leaflet.js"],
				dest: "inc/leaflet/leaflet.js"
			},
			leafletjs_images: {
				cwd: 'node_modules/leaflet/dist/images',
				src: '*',
        dest: 'inc/leaflet/images',
        expand: true
			}
    },
    cssmin: {
      options : {
        processImport: false,
				specialComments: 0
      },
      leafletjs: {
				files: [{
					src: ['node_modules/leaflet/dist/leaflet.css'],
					dest: 'inc/leaflet/leaflet.css'
				}]
      }
    },
		watch: {
			scripts: {
				files: ['inc/js/*.js', 'inc/js/*/*.js'],
				tasks: ['uglify'],
				options: {
					spawn: false
				}
			}
		}
  });

  // Load the plugins
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-watch');

  // Default task(s).
  grunt.registerTask('default', ['uglify', 'copy', 'cssmin']);

};