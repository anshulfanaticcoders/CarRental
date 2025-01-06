<script setup>
import Card from '../ui/card/Card.vue';
import CardContent from '../ui/card/CardContent.vue';
import CarouselContent from '../ui/carousel/CarouselContent.vue';
import CarouselItem from '../ui/carousel/CarouselItem.vue';
import CarouselNext from '../ui/carousel/CarouselNext.vue';
import CarouselPrevious from '../ui/carousel/CarouselPrevious.vue';

</script>

<template>
 <section
            class="category-carousel mt-[8rem] min-h-[50vh] py-customVerticalSpacing overflow-x-hidden"
        >
            <div
                class="flex min-h-[inherit] items-center justify-between gap-24 max-w-[1700px] ml-auto mr-[-100px]"
            >
                <div class="column">
                    <h2>
                        Our <br />
                        Categories
                    </h2>
                </div>
                <div
                    class="column carousel rounded-[20px] p-6"
                    style="
                        background: linear-gradient(
                            90deg,
                            rgba(21, 59, 79, 0.2) 0%,
                            rgba(21, 59, 79, 0) 94.4%
                        );
                    "
                >
                    <Carousel
                        class="relative w-full"
                        :opts="{
                            align: 'start',
                        }"
                    >
                        <CarouselContent>
                            <CarouselItem
                                v-for="(_, index) in 5"
                                :key="index"
                                class="md:basis-1/2 lg:basis-1/3"
                            >
                                <div class="p-1">
                                    <Link href="">
                                        <Card>
                                            <CardContent
                                                class="cardContent flex h-[415px] items-center justify-center p-6 relative"
                                            >
                                                <img
                                                    class="rounded-[20px] h-full w-full object-cover"
                                                    :src="categoryImage"
                                                    alt=""
                                                />

                                                <div
                                                    class="category_name absolute bottom-10 left-0 flex justify-between w-full px-8"
                                                >
                                                    <span
                                                        class="text-white text-[2rem] font-semibold"
                                                        >Sports</span
                                                    >
                                                    <img
                                                        class=""
                                                        :src="goIcon"
                                                        alt=""
                                                    />
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </Link>
                                </div>
                            </CarouselItem>
                        </CarouselContent>
                        <CarouselPrevious />
                        <CarouselNext />
                    </Carousel>
                </div>
            </div>
        </section>
</template>

<style>

</style>