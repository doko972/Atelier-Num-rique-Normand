<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Centre de ressources : articles, fiches pratiques, pages, questions
 * fréquentes, témoignages et documents téléchargeables (codex §14).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->string('summary', 255)->nullable();
            $table->string('status', 20)->default('published');
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'position']);
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('excerpt', 400);
            $table->longText('body');
            $table->string('image_path', 255)->nullable();
            $table->string('image_alt', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('reading_minutes')->nullable();
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });

        Schema::create('practical_guides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('summary', 400);
            $table->text('introduction')->nullable();
            $table->string('level', 20)->default('beginner');
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->text('prerequisites')->nullable();
            // Encart de sécurité affiché en évidence sur la fiche.
            $table->text('safety_notice')->nullable();
            $table->text('conclusion')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('image_alt', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            // Date de dernière vérification du contenu : une procédure
            // administrative peut changer, la fiche doit rester à jour.
            $table->date('reviewed_on')->nullable();
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });

        Schema::create('guide_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('practical_guide_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('title', 200);
            $table->text('body');
            $table->string('image_path', 255)->nullable();
            // Texte alternatif obligatoire dès qu'une capture d'écran existe.
            $table->string('image_alt', 255)->nullable();
            $table->text('tip')->nullable();
            $table->timestamps();

            $table->index(['practical_guide_id', 'position']);
        });

        Schema::create('downloadable_files', function (Blueprint $table): void {
            $table->id();
            // Rattachement polymorphe : fiche pratique, atelier, page…
            $table->nullableMorphs('attachable');
            $table->string('title', 200);
            $table->string('description', 400)->nullable();
            $table->string('disk', 30)->default('public');
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum', 64)->nullable();
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_public');
        });

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            // Les pages système (mentions légales, accessibilité…) ne peuvent
            // pas être supprimées car des liens du pied de page en dépendent.
            $table->string('key', 60)->nullable()->unique();
            $table->string('summary', 400)->nullable();
            $table->longText('body');
            $table->string('status', 20)->default('published');
            $table->boolean('is_system')->default(false);
            $table->boolean('show_in_footer')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'position']);
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('question', 300);
            $table->text('answer');
            $table->string('category', 80)->default('general');
            $table->string('status', 20)->default('published');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category', 'position']);
        });

        Schema::create('testimonials', function (Blueprint $table): void {
            $table->id();
            $table->text('quote');
            // Prénom seul, ou initiale : les témoignages sont anonymisables.
            $table->string('author_name', 100)->nullable();
            $table->string('author_context', 150)->nullable();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->date('collected_on')->nullable();
            // Preuve de l'accord donné pour la publication du témoignage.
            $table->boolean('publication_consent')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'position']);
        });

        Schema::create('official_links', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 180);
            $table->string('url', 255);
            $table->string('description', 400)->nullable();
            $table->string('category', 80)->default('security');
            $table->string('status', 20)->default('published');
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_links');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('downloadable_files');
        Schema::dropIfExists('guide_steps');
        Schema::dropIfExists('practical_guides');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('article_categories');
    }
};
